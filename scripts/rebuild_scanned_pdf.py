import argparse
import csv
import io
import json
import re
import subprocess
import sys
import tempfile
from collections import defaultdict
from pathlib import Path
from statistics import median

import cv2
import numpy as np
import pymupdf as fitz


MIN_WORDS = 8
MIN_PAPER_FRACTION = .70
MAX_SATURATED_FRACTION = .15
MIN_TEXT_DENSITY = .0005
# Tinta propia mínima y cobertura de línea para distinguir figuras de residuos OCR.
MIN_OWN_INK_DENSITY = .015
LINE_CROP_COVERAGE = .5
CROP_MARGIN = 4
# Criterio de modo auto: ante la duda la imagen queda intacta; el botón "Transcribir con OCR" fuerza el resto.
# Una página con más texto digital que esto (pies de verificación, timbres) no es un escaneo.
MAX_FOOTER_CHARS = 400
MIN_SCANNED_SHARE = .8
# Tinta que no es texto reconocido a partir de la cual la imagen se considera figura.
MAX_NON_TEXT_INK = .35
LONG_LINE_FRACTION = .25
SCAN_EDGE = .01
MIN_FIGURE_RULES = 3
MIN_RELIABLE_SHARE = .6
# Lectura mínima para escribir como texto un trozo con alto de línea; bajo esto se conserva como imagen.
MIN_RECOVERED_CONFIDENCE = 20
MIN_RULE_WIDTH = .2


def overlap(rect, other):
    return (rect & other).get_area() / rect.get_area() if rect.get_area() else 0


def visible_chars(page):
    return sum(len(s.get('chars') or ()) for s in page.get_texttrace() if s.get('type', 0) != 3)


def has_long_lines(ink):
    """Marcos, ejes y filas: una hoja escrita solo tiene, a lo sumo, algún subrayado horizontal suelto."""
    height, width = ink.shape
    # El borde de la imagen queda fuera: ahí están las sombras del escáner, no el contenido.
    ink = ink[int(height * SCAN_EDGE):int(height * (1 - SCAN_EDGE)), int(width * SCAN_EDGE):int(width * (1 - SCAN_EDGE))]
    horizontal = cv2.morphologyEx(ink, cv2.MORPH_OPEN, np.ones((1, max(1, int(width * LONG_LINE_FRACTION))), np.uint8))
    vertical = cv2.morphologyEx(ink, cv2.MORPH_OPEN, np.ones((max(1, int(height * LONG_LINE_FRACTION)), 1), np.uint8))
    return cv2.countNonZero(vertical) > 0 or cv2.connectedComponents(horizontal)[0] - 1 >= MIN_FIGURE_RULES


def is_rotated(image, directory, tesseract):
    """Un escaneo girado se lee como basura; si la detección falla, se asume derecho."""
    png = str(Path(directory) / 'osd.png')
    Path(png).write_bytes(cv2.imencode('.png', image)[1].tobytes())
    result = subprocess.run([tesseract, png, 'stdout', '-l', 'osd', '--psm', '0'], capture_output=True, encoding='utf-8', errors='replace')
    rotate = re.search(r'Rotate: (\d+)', result.stdout)
    confidence = re.search(r'Orientation confidence: ([\d.]+)', result.stdout)
    return bool(rotate and confidence) and int(rotate[1]) != 0 and float(confidence[1]) >= 2


def candidates(page):
    area = page.rect.get_area()
    visible = [fitz.Rect(s['bbox']) for s in page.get_texttrace() if s.get('type', 0) != 3 and s.get('chars')]
    images = [(image[0], rect) for image in page.get_images() for rect in page.get_image_rects(image)]
    return [(xref, rect) for xref, rect in images if rect.get_area() >= area * .05
            and min(rect.width, rect.height) >= 72
            and sum(overlap(rect, box) for box in visible) <= .1]


def ocr(image, directory, tesseract, language, psm):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    gray = cv2.resize(gray, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
    gray = cv2.GaussianBlur(gray, (3, 3), 0)
    binary = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)[1]
    binary = cv2.copyMakeBorder(binary, 40, 40, 40, 40, cv2.BORDER_CONSTANT, value=255)
    png = str(Path(directory) / 'ocr.png')
    Path(png).write_bytes(cv2.imencode('.png', binary)[1].tobytes())
    result = subprocess.run([tesseract, png, 'stdout', '-l', language, '--psm', str(psm), '--dpi', '600', 'tsv'], check=True, capture_output=True, encoding="utf-8", errors="replace").stdout
    return list(csv.DictReader(io.StringIO(result), delimiter='\t'))


def rebuild(source, target, language, pages, tesseract, extra_pages, max_pages):
    doc = fitz.open(source)
    if doc.needs_pass:
        return 3
    selected = list(range(len(doc))) if pages in ('all', 'auto') else [int(p) - 1 for p in pages.split(',')]
    if extra_pages:
        selected = sorted(set(selected) | {int(p) - 1 for p in extra_pages.split(',')})
    if any(i < 0 or i >= len(doc) for i in selected):
        raise ValueError('Número de página fuera de rango')
    plans = {}
    for i in selected:
        page = doc[i]
        regions = candidates(page)
        visible = any(s.get('type', 0) != 3 and s.get('chars') for s in page.get_texttrace())
        if regions or (pages == 'all' and not visible):
            plans[i] = regions or [(None, page.rect)]
    if max_pages and len(plans) > max_pages:
        raise ValueError('El PDF supera el máximo de páginas permitido para OCR')
    lines_count = regions_count = regions_ocr = 0
    rebuilt = []
    auto = pages == 'auto'
    page_chars = {i: visible_chars(doc[i]) for i in range(len(doc))} if auto else {}
    scanned_document = auto and sum(chars <= MAX_FOOTER_CHARS for chars in page_chars.values()) >= len(doc) * MIN_SCANNED_SHARE
    with tempfile.TemporaryDirectory() as directory:
        for index, regions in plans.items():
            page = doc[index]
            chars = page_chars.get(index, 0)
            # En un PDF digital solo cuenta una página sin ningún texto; el resto del documento no se toca.
            if auto and (chars > MAX_FOOTER_CHARS or (not scanned_document and chars > 0)):
                continue
            # Sin texto digital en todo un documento escaneado no hay con qué dudar: la página es el escaneo.
            judge_figure = auto and not (scanned_document and chars == 0)
            print(f'PROGRESS {list(plans).index(index) + 1} {len(plans)}', flush=True)
            changed = False
            for xref, rect in regions:
                pix = page.get_pixmap(clip=rect, dpi=300, alpha=False)
                image = cv2.cvtColor(np.frombuffer(pix.samples, dtype=np.uint8).reshape(pix.height, pix.width, pix.n), cv2.COLOR_RGB2BGR)
                if judge_figure:
                    hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)
                    paper = (hsv[:, :, 2] >= 180) & (hsv[:, :, 1] <= 55)
                    if np.mean(paper) < MIN_PAPER_FRACTION or np.mean(hsv[:, :, 1] > 80) > MAX_SATURATED_FRACTION:
                        continue
                tsv = ocr(image, directory, tesseract, language, 3)
                regions_ocr += 1
                # La orientación falla en hojas derechas: solo se consulta si la lectura salió mala.
                legible = [float(w['conf']) for w in tsv if float(w['conf']) >= 0 and any(c.isalnum() for c in w['text'])]
                if legible and sum(conf >= 60 for conf in legible) / len(legible) < MIN_RELIABLE_SHARE and is_rotated(image, directory, tesseract):
                    continue
                gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
                ink = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)[1]
                if judge_figure and has_long_lines(ink):
                    continue
                total_ink = cv2.countNonZero(ink)
                groups = defaultdict(list)
                words = []
                heights = []
                for word in tsv:
                    text = word['text'].strip()
                    confidence = float(word['conf'])
                    x, y = ((int(word[k]) - 40) / 2 for k in ('left', 'top'))
                    w, h = (int(word[k]) / 2 for k in ('width', 'height'))
                    if confidence >= 0 and h > 0:
                        heights.append(h)
                    if confidence < 20 or not any(c.isalnum() for c in text) or not w or not h:
                        continue
                    key = (word['block_num'], word['par_num'], word['line_num'])
                    item = (x, y, w, h, text)
                    words.append((key, item, confidence))
                    if confidence >= 60:
                        groups[key].append(item)
                        cv2.rectangle(ink, (max(0, int(x-4)), max(0, int(y-4))), (int(x+w+4), int(y+h+4)), 0, -1)
                reliable = [word for line in groups.values() for word in line]
                density = sum(w[2] * w[3] for w in reliable) / (pix.width * pix.height)
                if len(reliable) < MIN_WORDS or (judge_figure and density < MIN_TEXT_DENSITY):
                    continue
                if judge_figure and total_ink and cv2.countNonZero(ink) / total_ink > MAX_NON_TEXT_INK:
                    continue
                line_heights = [max(w[1]+w[3] for w in line) - min(w[1] for w in line) for line in groups.values()]
                typical_height = median(line_heights or heights or [1])
                scale_x, scale_y = rect.width / pix.width, rect.height / pix.height
                print('STAGE images', flush=True)
                joined = cv2.dilate(ink, np.ones((7, 25), np.uint8))
                _, labels, stats, _ = cv2.connectedComponentsWithStats(joined)
                image_boxes = []
                for component, (x, y, w, h, area) in enumerate(stats[1:], 1):
                    line_like = h <= 1.8 * typical_height
                    # Una tinta mucho más baja que el texto (regla, subrayado) no se intenta leer.
                    if line_like and h >= .5 * typical_height:
                        x0, y0 = max(0, x-6), max(0, y-6)
                        x1, y1 = min(pix.width, x+w+6), min(pix.height, y+h+6)
                        recovered = []
                        for word in ocr(image[y0:y1, x0:x1], directory, tesseract, language, 7):
                            text = word['text'].strip()
                            confidence = float(word['conf'])
                            if any(c.isalnum() for c in text) and confidence >= 0:
                                wx = x0 + (int(word['left']) - 40) / 2
                                wy = y0 + (int(word['top']) - 40) / 2
                                ww, wh = int(word['width']) / 2, int(word['height']) / 2
                                recovered.append(((wx, wy, ww, wh, text), confidence))
                        # Tinta con alto de línea: se escribe si se lee con confianza; si no, se conserva como
                        # imagen más abajo (una firma fina o una regla nunca se descartan ni se vuelven caracteres).
                        if recovered and sum(conf for _, conf in recovered) / len(recovered) >= MIN_RECOVERED_CONFIDENCE:
                            existing = [(key, item) for key, line in groups.items() for item in line]
                            for item, _ in recovered:
                                box = fitz.Rect(item[0], item[1], item[0]+item[2], item[1]+item[3])
                                if any(overlap(box, fitz.Rect(w[0]-3, w[1]-3, w[0]+w[2]+3, w[1]+w[3]+3)) >= .2 for _, w in existing):
                                    continue
                                same_line = [(key, w) for key, w in existing
                                             if min(box.y1, w[1]+w[3]) - max(box.y0, w[1]) > min(box.height, w[3]) * .4]
                                key = min(same_line, key=lambda pair: abs(pair[1][0]-item[0]))[0] if same_line else ('retry', str(x), str(y))
                                groups[key].append(item)
                            continue
                    own = ink[y:y+h, x:x+w].copy()
                    own[labels[y:y+h, x:x+w] != component] = 0
                    for line in groups.values():
                        lx0 = int(min(item[0] for item in line)) - 4 - x
                        ly0 = int(min(item[1] for item in line)) - 4 - y
                        lx1 = int(max(item[0]+item[2] for item in line)) + 4 - x
                        ly1 = int(max(item[1]+item[3] for item in line)) + 4 - y
                        cv2.rectangle(own, (lx0, ly0), (lx1, ly1), 0, -1)
                    if cv2.countNonZero(own) == 0 or (not line_like and cv2.countNonZero(own) < w * h * MIN_OWN_INK_DENSITY):
                        continue
                    ox, oy, ow, oh = cv2.boundingRect(cv2.findNonZero(own))
                    rule = w >= pix.width * MIN_RULE_WIDTH or h >= pix.height * MIN_RULE_WIDTH
                    if (line_like or area >= pix.width * pix.height * .002) and w >= 8 and h >= 8 and (rule or (w <= h * 30 and h <= w * 30)):
                        image_boxes.append((max(0, x+ox-CROP_MARGIN), max(0, y+oy-CROP_MARGIN), min(pix.width, x+ox+ow+CROP_MARGIN), min(pix.height, y+oy+oh+CROP_MARGIN)))
                crops = []
                for x0, y0, x1, y1 in image_boxes:
                    if x1-x0 < 8 or y1-y0 < 8 or (x1-x0)*(y1-y0) < min(pix.width * pix.height * .002, typical_height * typical_height):
                        continue
                    ok, data = cv2.imencode('.png', image[y0:y1, x0:x1])
                    if ok:
                        crops.append((fitz.Rect(rect.x0+x0*scale_x, rect.y0+y0*scale_y, rect.x0+x1*scale_x, rect.y0+y1*scale_y), data.tobytes()))
                if xref is None:
                    page.clean_contents()
                    empty = doc.get_new_xref()
                    doc.update_object(empty, '<<>>')
                    doc.update_stream(empty, b'')
                    page.set_contents(empty)
                else:
                    others = [(img[0], r) for img in page.get_images() for r in page.get_image_rects(img)
                              if (img[0] != xref or r != rect) and r.intersects(rect)]
                    page.add_redact_annot(rect, fill=False)
                    page.apply_redactions(images=fitz.PDF_REDACT_IMAGE_REMOVE, graphics=fitz.PDF_REDACT_LINE_ART_NONE, text=fitz.PDF_REDACT_TEXT_NONE)
                    for other_xref, other_rect in others:
                        page.insert_image(other_rect, xref=other_xref)
                    visible = [fitz.Rect(s['bbox']) for s in page.get_texttrace() if s.get('type', 0) != 3 and s.get('chars')]
                    for span in page.get_texttrace():
                        box = fitz.Rect(span['bbox'])
                        if span.get('type') == 3 and box.intersects(rect) and not any(box.intersects(v) for v in visible):
                            page.add_redact_annot(box, fill=False)
                    page.apply_redactions(images=fitz.PDF_REDACT_IMAGE_NONE, graphics=fitz.PDF_REDACT_LINE_ART_NONE, text=fitz.PDF_REDACT_TEXT_REMOVE)
                for box, data in crops:
                    page.insert_image(box, stream=data)
                    regions_count += 1
                for words in groups.values():
                    words.sort(key=lambda word: word[0])
                    x = min(w[0] for w in words); y = min(w[1] for w in words)
                    x1 = max(w[0]+w[2] for w in words); y1 = max(w[1]+w[3] for w in words)
                    box = fitz.Rect(rect.x0+x*scale_x, rect.y0+y*scale_y, rect.x0+x1*scale_x, rect.y0+y1*scale_y)
                    if any(overlap(box, crop) >= LINE_CROP_COVERAGE for crop, _ in crops):
                        continue
                    line = ' '.join(w[4] for w in words)
                    line = re.sub(r'\bRIT 0-(?=\d)', 'RIT O-', line)
                    # Las líneas de alto normal usan el tamaño típico de la región (texto parejo en el Word);
                    # títulos y líneas atípicas conservan el suyo.
                    typical_size = typical_height * scale_y * .8
                    atypical = not .6 <= box.height * .8 / typical_size <= 1.5
                    # Solo una línea corta puede ser un título; en una larga, el alto anómalo es ruido del OCR.
                    size = min(box.height * .8, typical_size * 1.5) if atypical and len(words) < 6 else typical_size
                    # Una caja anómala no puede agrandar la línea más allá del ancho que ocupaba en el escaneo.
                    width = fitz.get_text_length(line, fontname='helv', fontsize=size)
                    if width > box.width > 0:
                        size *= box.width / width
                    page.insert_text((box.x0, box.y1), line, fontsize=max(4, size), fontname='helv')
                    lines_count += 1
                changed = True
            if changed:
                rebuilt.append(index + 1)
    doc.save(target, garbage=4, deflate=True)
    print('SUMMARY ' + json.dumps({'pages_rebuilt': rebuilt, 'text_lines': lines_count, 'image_regions': regions_count, 'regions_ocr': regions_ocr}), flush=True)
    return 0


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('input'); parser.add_argument('output')
    parser.add_argument('--lang', default='spa'); parser.add_argument('--pages', default='auto')
    parser.add_argument('--tesseract', default='tesseract')
    parser.add_argument('--extra-pages', default='')
    parser.add_argument('--max-pages', type=int)
    args = parser.parse_args()
    try:
        sys.exit(rebuild(args.input, args.output, args.lang, args.pages, args.tesseract, args.extra_pages, args.max_pages))
    except Exception as error:
        print(str(error), file=sys.stderr)
        sys.exit(1)
