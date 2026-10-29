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


def overlap(rect, other):
    return (rect & other).get_area() / rect.get_area() if rect.get_area() else 0


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
    cv2.imwrite(png, binary)
    result = subprocess.run([tesseract, png, 'stdout', '-l', language, '--psm', str(psm), '--dpi', '600', 'tsv'], check=True, capture_output=True, text=True).stdout
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
    with tempfile.TemporaryDirectory() as directory:
        for index, regions in plans.items():
            page = doc[index]
            print(f'PROGRESS {list(plans).index(index) + 1} {len(plans)}', flush=True)
            changed = False
            for xref, rect in regions:
                pix = page.get_pixmap(clip=rect, dpi=300, alpha=False)
                png = str(Path(directory) / 'region.png')
                pix.save(png)
                image = cv2.imread(png)
                tsv = ocr(image, directory, tesseract, language, 3)
                regions_ocr += 1
                gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
                ink = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)[1]
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
                if xref is not None and sum(map(len, groups.values())) < 3:
                    continue
                line_heights = [max(w[1]+w[3] for w in line) - min(w[1] for w in line) for line in groups.values()]
                typical_height = median(line_heights or heights or [1])
                scale_x, scale_y = rect.width / pix.width, rect.height / pix.height
                print('STAGE images', flush=True)
                joined = cv2.dilate(ink, np.ones((7, 25), np.uint8))
                _, _, stats, _ = cv2.connectedComponentsWithStats(joined)
                image_boxes = []
                for x, y, w, h, area in stats[1:]:
                    line_like = h <= 1.8 * typical_height
                    if line_like:
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
                        # Tinta con alto de línea: se escribe la mejor lectura aunque la confianza sea baja; si
                        # Tesseract no lee nada, se conserva como imagen más abajo (nunca se descarta).
                        if recovered:
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
                    big_enough = line_like or area >= pix.width * pix.height * .002
                    if big_enough and w >= 8 and h >= 8 and w <= h * 30 and h <= w * 30:
                        image_boxes.append((max(0, x-4), max(0, y-4), min(pix.width, x+w+4), min(pix.height, y+h+4)))
                line_boxes = [fitz.Rect(min(w[0] for w in line), min(w[1] for w in line),
                                        max(w[0]+w[2] for w in line), max(w[1]+w[3] for w in line))
                              for line in groups.values()]
                crops = []
                for x0, y0, x1, y1 in image_boxes:
                    parts = [fitz.Rect(x0, y0, x1, y1)]
                    for line in line_boxes:
                        remaining = []
                        for part in parts:
                            if overlap(line, part) < .3:
                                remaining.append(part)
                                continue
                            intersection = part & line
                            remaining.extend([fitz.Rect(part.x0, part.y0, part.x1, intersection.y0),
                                              fitz.Rect(part.x0, intersection.y1, part.x1, part.y1)])
                        parts = remaining
                    for part in parts:
                        x0, y0, x1, y1 = map(int, (part.x0, part.y0, part.x1, part.y1))
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
                    if any(overlap(box, crop) >= .5 for crop, _ in crops):
                        continue
                    line = ' '.join(w[4] for w in words)
                    line = re.sub(r'\bRIT 0-(?=\d)', 'RIT O-', line)
                    # Las líneas de alto normal usan el tamaño típico de la región (texto parejo en el Word);
                    # títulos y líneas atípicas conservan el suyo.
                    typical_size = typical_height * scale_y * .8
                    size = typical_size if .6 <= box.height * .8 / typical_size <= 1.5 else box.height * .8
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
