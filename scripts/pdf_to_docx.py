"""PDF -> DOCX with paragraph/table reconstruction (pdf2docx), shaped like Acrobat XI output.

Usage: python3 -I pdf_to_docx.py <input.pdf> <output.docx>

Before reconstruction, content repeated at the same position in the top/bottom band of
most pages (letterheads, logos, addresses, QR/validation blocks) is moved to each page's
Word header/footer as page-anchored objects,
and form/annotation appearances (e.g. signature boxes) are kept as images in place.
"""

import io
import itertools
import re
import sys
import tempfile
from pathlib import Path
from xml.sax.saxutils import escape

import pymupdf
from docx import Document
from docx.enum.section import WD_SECTION_START
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls, qn
from docx.shared import Pt
from pdf2docx import Converter
from pdf2docx.layout.Blocks import Blocks

TOLERANCE = 2.0
BAND = 0.2  # share of the page height, at top and bottom, where letterheads and footers live
MIN_REPEATS = 3  # pages an element must repeat on to be a letterhead/footer (merged PDFs have several)
PAGE_NUMBER = re.compile(r'^((?:p[áa]g(?:ina)?\.?\s*)?)(\d{1,5})$', re.IGNORECASE)
EMU_PER_PT = 12700
VML_NS = 'xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"'


def same_box(a, b):
    return all(abs(x - y) <= TOLERANCE for x, y in zip(a, b))


def in_band(page, box):
    band = page.rect.height * BAND
    return box[3] <= band or box[1] >= page.rect.height - band


def repeated_artifacts(doc):
    """Images and text blocks drawn at the same place in the top/bottom band of several pages.

    Merged PDFs carry a different letterhead/footer per original document, and some pages
    (e.g. a final signature page) omit it, so an artifact only needs to repeat on three pages,
    or on half of a short document. Each artifact records its pages: those pages get it in their
    header/footer, the others do not. Page numbers are detected separately (they change).
    """
    if doc.page_count < 2:
        return []

    artifacts = []
    for number, page in enumerate(doc):
        found = [('image', info[0], tuple(rect), None)
                 for info in page.get_images(full=True) for rect in page.get_image_rects(info[0])]
        for block in page.get_text('dict')['blocks']:
            if block['type'] == 0:
                text = ' '.join(s['text'] for line in block['lines'] for s in line['spans']).strip()
                if text and not PAGE_NUMBER.match(text):
                    found.append(('text', text, tuple(block['bbox']), block))

        for kind, key, box, block in found:
            if not in_band(page, box):
                continue
            match = next((a for a in artifacts if a['kind'] == kind and a['key'] == key and same_box(a['box'], box)), None)
            if match is None:
                artifacts.append({'kind': kind, 'key': key, 'box': box, 'block': block, 'pages': {number}, 'boxes': {number: box}})
            else:
                match['pages'].add(number)
                match['boxes'][number] = box

    return [a for a in artifacts if repeats_enough(a['pages'], doc.page_count)]


def repeats_enough(pages, page_count):
    """On MIN_REPEATS pages (a run in a merged PDF) or, in short documents, on at least half of them."""
    return len(pages) >= 2 and (len(pages) >= MIN_REPEATS or len(pages) * 2 >= page_count)


def page_number_artifacts(doc):
    """Printed page numbers: an integer (optionally after «Página»/«Pág.») in the top/bottom band, at the
    same height and alignment, increasing by one per page. They become a Word PAGE field in the footer."""
    groups = []
    for number, page in enumerate(doc):
        for block in page.get_text('dict')['blocks']:
            if block['type'] != 0:
                continue
            for line in block['lines']:
                text = ''.join(s['text'] for s in line['spans']).strip()
                match = PAGE_NUMBER.match(text)
                box = tuple(line['bbox'])
                if not match or not in_band(page, box):
                    continue
                spans = [s for s in line['spans'] if s['text'].strip()]
                text_box = (min(s['bbox'][0] for s in spans), box[1], max(s['bbox'][2] for s in spans), box[3])
                offset = int(match[2]) - number
                group = next((g for g in groups if g['prefix'] == match[1] and g['offset'] == offset
                              and abs(g['box'][1] - box[1]) <= TOLERANCE and aligned(g['text_box'], text_box)), None)
                if group is None:
                    group = {'kind': 'pagenum', 'prefix': match[1], 'offset': offset, 'box': box, 'text_box': text_box,
                             'line': line, 'pages': set(), 'boxes': {}, 'texts': {}}
                    groups.append(group)
                group['pages'].add(number)
                group['boxes'][number] = box
                group['texts'][number] = text_box

    kept = []
    for group in groups:
        if not repeats_enough(group['pages'], doc.page_count):
            continue
        texts = list(group['texts'].values())
        group['align'] = ('right' if all(abs(t[2] - texts[0][2]) <= TOLERANCE for t in texts)
                          else 'center' if all(abs((t[0] + t[2]) - (texts[0][0] + texts[0][2])) <= 2 * TOLERANCE for t in texts)
                          else 'left')
        group['box'] = (min(b[0] for b in group['boxes'].values()), group['box'][1],
                        max(b[2] for b in group['boxes'].values()), max(b[3] for b in group['boxes'].values()))
        kept.append(group)
    return kept


def aligned(a, b):
    """Same left edge, right edge or centre (numbers of different width keep their alignment)."""
    return (abs(a[0] - b[0]) <= TOLERANCE or abs(a[2] - b[2]) <= TOLERANCE
            or abs((a[0] + a[2]) - (b[0] + b[2])) <= 2 * TOLERANCE)


def image_png(doc, xref):
    pixmap = pymupdf.Pixmap(doc, xref)
    smask = doc.extract_image(xref).get('smask') or 0
    if smask:
        pixmap = pymupdf.Pixmap(pixmap, pymupdf.Pixmap(doc, smask))
    if pixmap.colorspace and pixmap.colorspace.n > 3:
        pixmap = pymupdf.Pixmap(pymupdf.csRGB, pixmap)
    return pixmap.tobytes('png')


def flatten_annotations(page):
    """Replace widgets/annotations (signatures, stamps) with an image of their appearance."""
    items = [w for w in page.widgets()] + [a for a in page.annots() if a.type[0] != pymupdf.PDF_ANNOT_LINK]
    # 1pt of padding keeps border strokes drawn on the rect edge.
    rects = [pymupdf.Rect(i.rect) + (-1, -1, 1, 1) for i in items]
    shots = [(rect, page.get_pixmap(clip=rect, matrix=pymupdf.Matrix(4, 4), annots=True)) for rect in rects]
    for item in items:
        if isinstance(item, pymupdf.Widget):
            page.delete_widget(item)
        else:
            page.delete_annot(item)
    for rect, shot in shots:
        if not rect.is_empty:
            page.insert_image(rect, pixmap=shot)


def signature_boxes(page):
    """Flattened e-signature appearances: a stroked frame holding an image and text, with a filled colour
    bar on its left edge, as the Poder Judicial stamps them. pdf2docx would read them as a table and
    paint the bar's colour over a whole cell, so they are kept as a picture instead."""
    drawings = page.get_drawings()
    frames = [d['rect'] for d in drawings if d.get('color') is not None and d['rect'].width > 80 and d['rect'].height > 30]
    bars = [d['rect'] for d in drawings if d.get('fill') not in (None, (1.0, 1.0, 1.0)) and d['rect'].height > 30]
    images = [r for info in page.get_images(full=True) for r in page.get_image_rects(info[0])]
    boxes = []
    for frame in frames:
        has_bar = any(abs(b.x0 - frame.x0) <= TOLERANCE and abs(b.height - frame.height) <= TOLERANCE
                      and b.width < frame.width * 0.2 for b in bars)
        has_image = any(frame.contains(r) or frame.intersects(r) and (r & frame).get_area() > 0.5 * r.get_area() for r in images)
        text = ' '.join(page.get_textbox(frame).split())
        if has_bar and has_image and text and not any(frame in b or b.contains(frame) for b in boxes):
            boxes.append(pymupdf.Rect(frame))

    # Signatures side by side become one picture: as separate inline pictures they would not fit the
    # text width together and the second one would wrap onto another line (or page).
    rows = []
    for box in sorted(boxes, key=lambda b: (b.y0, b.x0)):
        row = next((r for r in rows if box.y0 < r[-1].y1 and r[-1].y0 < box.y1), None)
        if row is None:
            rows.append([box])
        else:
            row.append(box)
    found = []
    for row in rows:
        union = pymupdf.Rect(row[0])
        for box in row[1:]:
            union |= box
        found.append((union, ' / '.join(' '.join(page.get_textbox(box).split()) for box in row)))
    return found


def flatten_signature_boxes(page):
    """Replace each flattened signature box with an image of it; returns (rect, text) for alt text."""
    found = signature_boxes(page)
    shots = [(box + (-1, -1, 1, 1), text) for box, text in found]
    pictures = [(rect, page.get_pixmap(clip=rect, matrix=pymupdf.Matrix(4, 4)), text) for rect, text in shots]
    for rect, _, _ in pictures:
        page.add_redact_annot(rect)
    if pictures:
        page.apply_redactions(images=pymupdf.PDF_REDACT_IMAGE_REMOVE, graphics=pymupdf.PDF_REDACT_LINE_ART_REMOVE_IF_COVERED)
    for rect, shot, _ in pictures:
        page.insert_image(rect, pixmap=shot)
    return [(page.number, rect, text) for rect, _, text in pictures]


def describe_pictures(word, pictures):
    """Sets the alt text of the pictures that replaced signature boxes (matched by page and size)."""
    pending = {}
    for number, rect, text in pictures:
        pending.setdefault(number, []).append((round(rect.width * EMU_PER_PT), round(rect.height * EMU_PER_PT), text))
    pages = section_pages(word)
    section = 0
    for paragraph in word.element.body.iter(qn('w:p')):
        page_number = pages[min(section, len(pages) - 1)]
        for drawing in paragraph.iter(qn('wp:inline'), qn('wp:anchor')):
            extent = drawing.find(qn('wp:extent'))
            for item in pending.get(page_number, []):
                if abs(int(extent.get('cx')) - item[0]) <= item[0] * 0.02 and abs(int(extent.get('cy')) - item[1]) <= item[1] * 0.02:
                    drawing.find(qn('wp:docPr')).set('descr', 'Firma electrónica: ' + item[2])
                    pending[page_number].remove(item)
                    break
        if paragraph.find(f'{qn("w:pPr")}/{qn("w:sectPr")}') is not None:
            section += 1


def section_pages(word):
    """PDF page index of each Word section: pdf2docx opens every page with a new-page section and
    adds continuous ones for column layouts."""
    pages, page_number = [], -1
    for index, section in enumerate(word.sections):
        if index == 0 or section.start_type != WD_SECTION_START.CONTINUOUS:
            page_number += 1
        pages.append(page_number)
    return pages


def anchor_image_xml(rid, box, index):
    x0, y0, x1, y1 = (round(v * EMU_PER_PT) for v in box)
    cx, cy = x1 - x0, y1 - y0
    return (
        f'<w:r {nsdecls("w", "wp", "a", "pic", "r")}><w:drawing>'
        f'<wp:anchor distT="0" distB="0" distL="0" distR="0" simplePos="0" relativeHeight="{index}" behindDoc="1" locked="0" layoutInCell="1" allowOverlap="1">'
        f'<wp:simplePos x="0" y="0"/><wp:positionH relativeFrom="page"><wp:posOffset>{x0}</wp:posOffset></wp:positionH>'
        f'<wp:positionV relativeFrom="page"><wp:posOffset>{y0}</wp:posOffset></wp:positionV>'
        f'<wp:extent cx="{cx}" cy="{cy}"/><wp:effectExtent l="0" t="0" r="0" b="0"/><wp:wrapNone/>'
        f'<wp:docPr id="{9000 + index}" name="Artifact {index}"/><wp:cNvGraphicFramePr/>'
        f'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic>'
        f'<pic:nvPicPr><pic:cNvPr id="{9000 + index}" name="artifact{index}.png"/><pic:cNvPicPr/></pic:nvPicPr>'
        f'<pic:blipFill><a:blip r:embed="{rid}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
        f'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{cx}" cy="{cy}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
        f'</pic:pic></a:graphicData></a:graphic></wp:anchor></w:drawing></w:r>'
    )


def font_name(name):
    name = name.split('+')[-1].split('-')[0].split(',')[0]
    name = re.sub(r'(PSMT|MT|PS)$', '', name)
    return 'Arial' if name.lower().startswith('helvetica') else name


def run_xml(span):
    name = escape(font_name(span['font']), {'"': '&quot;'})
    bold = span['flags'] & 16 or 'bold' in span['font'].lower()
    props = f'<w:rFonts w:ascii="{name}" w:hAnsi="{name}" w:cs="{name}"/>' + ('<w:b/>' if bold else '') + ('<w:i/>' if span['flags'] & 2 else '')
    if span.get('color'):
        props += f'<w:color w:val="{span["color"]:06X}"/>'
    size = round(span['size'] * 2)
    props += f'<w:sz w:val="{size}"/><w:szCs w:val="{size}"/>'
    return f'<w:r><w:rPr>{props}</w:rPr><w:t xml:space="preserve">{escape(span["text"])}</w:t></w:r>'


def physical_rows(lines):
    """PDF lines sharing a baseline row, left to right; space-only fragments are dropped unless the row is empty."""
    rows = []
    for line in sorted(lines, key=lambda l: (round(l['bbox'][1]), l['bbox'][0])):
        if rows and abs(rows[-1][0]['bbox'][1] - line['bbox'][1]) <= TOLERANCE:
            rows[-1].append(line)
        else:
            rows.append([line])
    has_text = lambda line: any(s['text'].strip() for s in line['spans'])
    return [[l for l in row if has_text(l)] or row[:1] for row in rows]


def text_box_xml(block, page_width, index):
    """A page-anchored VML text box per block (as Acrobat XI writes them); one paragraph per row, PDF line pitch.
    Further fragments on the same row are placed with tab stops at their PDF position."""
    x0, y0, x1, y1 = block['bbox']
    rows = physical_rows(block['lines'])
    paragraphs = ''
    for number, row in enumerate(rows):
        top, bottom = row[0]['bbox'][1], max(l['bbox'][3] for l in row)
        pitch = (rows[number + 1][0]['bbox'][1] - top) if number + 1 < len(rows) else bottom - top
        stops = ''.join(f'<w:tab w:val="left" w:pos="{round((l["bbox"][0] - x0) * 20)}"/>' for l in row[1:])
        runs = ''.join(('<w:r><w:tab/></w:r>' if i else '') + ''.join(run_xml(s) for s in l['spans']) for i, l in enumerate(row))
        paragraphs += (
            f'<w:p><w:pPr>{f"<w:tabs>{stops}</w:tabs>" if stops else ""}'
            f'<w:spacing w:before="0" w:after="0" w:line="{max(round(pitch * 20), 20)}" w:lineRule="exact"/>'
            f'<w:ind w:left="{round((row[0]["bbox"][0] - x0) * 20)}" w:right="0"/></w:pPr>{runs}</w:p>'
        )
    # Substituted fonts are often a little wider than the PDF ones: leave room so lines do not wrap.
    width = min((x1 - x0) * 1.1 + 2, page_width - x0)
    return (
        f'<w:r {nsdecls("w")} {VML_NS}><w:pict><v:shape id="artifact_text_{index}" type="#_x0000_t202" filled="f" stroked="f" '
        f'style="position:absolute;margin-left:{x0:.2f}pt;margin-top:{y0:.2f}pt;width:{width:.2f}pt;height:{y1 - y0:.2f}pt;'
        f'mso-position-horizontal-relative:page;mso-position-vertical-relative:page;z-index:-{index}">'
        f'<v:textbox inset="0,0,0,0" style="mso-fit-shape-to-text:t"><w:txbxContent>{paragraphs}</w:txbxContent></v:textbox></v:shape></w:pict></w:r>'
    )


def page_number_box_xml(group, page_width, index):
    """Page-anchored text box with the prefix and a PAGE field, aligned like the printed numbers."""
    x0, y0, x1, y1 = group['box']
    spans = [s for s in group['line']['spans'] if s['text'].strip()]
    number_span = spans[-1]
    prefix = run_xml({**spans[0], 'text': group['prefix']}) if group['prefix'] else ''
    field_props = run_xml(number_span).split('<w:t ')[0][len('<w:r>'):]
    field = f'<w:fldSimple w:instr=" PAGE "><w:r>{field_props}<w:t>{group["offset"] + min(group["pages"])}</w:t></w:r></w:fldSimple>'
    width = min(max(x1 - x0, 36) * 1.1 + 2, page_width - x0)
    return (
        f'<w:r {nsdecls("w")} {VML_NS}><w:pict><v:shape id="artifact_page_{index}" type="#_x0000_t202" filled="f" stroked="f" '
        f'style="position:absolute;margin-left:{x0:.2f}pt;margin-top:{y0:.2f}pt;width:{width:.2f}pt;height:{y1 - y0:.2f}pt;'
        f'mso-position-horizontal-relative:page;mso-position-vertical-relative:page;z-index:-{index}">'
        f'<v:textbox inset="0,0,0,0"><w:txbxContent><w:p><w:pPr><w:spacing w:before="0" w:after="0"/>'
        f'<w:jc w:val="{group["align"]}"/></w:pPr>{prefix}{field}</w:p></w:txbxContent></v:textbox></v:shape></w:pict></w:r>'
    )


def footer_top(page, artifacts):
    """Top of the highest footer element on this page (None without footer elements)."""
    tops = [a['boxes'][page.number][1] for a in artifacts if a['boxes'][page.number][1] >= page.rect.height / 2]
    return min(tops) if tops else None


def fill_header_footer(section, doc, artifacts, page, counter):
    """Writes this page's artifacts into the section's own header/footer."""
    header, footer = section.header, section.footer
    header.is_linked_to_previous = footer.is_linked_to_previous = False

    for artifact in artifacts:
        box = artifact['box']
        target = header if box[1] < page.rect.height / 2 else footer
        paragraph = target.paragraphs[0] if target.paragraphs else target.add_paragraph()
        index = next(counter)
        if artifact['kind'] == 'image':
            rid, _ = target.part.get_or_add_image(io.BytesIO(image_png(doc, artifact['key'])))
            paragraph._p.append(parse_xml(anchor_image_xml(rid, box, index)))
        elif artifact['kind'] == 'pagenum':
            paragraph._p.append(parse_xml(page_number_box_xml(artifact, page.rect.width, index)))
        else:
            paragraph._p.append(parse_xml(text_box_xml(artifact['block'], page.rect.width, index)))

    for part in (header, footer):
        anchor = part.paragraphs[0] if part.paragraphs else part.add_paragraph()
        anchor.paragraph_format.space_before = anchor.paragraph_format.space_after = Pt(0)
        anchor.paragraph_format.line_spacing = Pt(1)


def move_to_header_footer(word, doc, artifacts):
    """Each page gets exactly the artifacts it had; consecutive pages with the same set share one header/footer."""
    counter = itertools.count(1)
    previous = None
    pages = section_pages(word)

    for index, section in enumerate(word.sections):
        page_number = min(pages[index], doc.page_count - 1)
        new_page = index == 0 or pages[index] != pages[index - 1]
        page = doc[page_number]
        here = [a for a in artifacts if page_number in a['pages']]
        present = frozenset(id(a) for a in here)

        if index > 0 and present == previous:
            section.header.is_linked_to_previous = section.footer.is_linked_to_previous = True
        else:
            fill_header_footer(section, doc, here, page, counter)
            previous = present

        # Footer elements float, so the body may use the space beside them: only ever shrink the margin
        # pdf2docx derived from the body (enlarging it pushed page numbers onto extra pages).
        top = footer_top(page, here)
        if top is not None:
            section.bottom_margin = Pt(min(section.bottom_margin.pt, max(0, page.rect.height - top)))
        section.footer_distance = Pt(min(36, section.bottom_margin.pt / 2))
        # Word and LibreOffice push the body below the header paragraph: keep it inside the top margin.
        section.header_distance = Pt(max(0, min(36, section.top_margin.pt - 2)))

        # Printed page numbers continue from the PDF's numbering at the start of each numbered run.
        numbering = next((a for a in here if a['kind'] == 'pagenum'), None)
        if new_page and numbering and page_number - 1 not in numbering['pages']:
            set_page_number_start(section, numbering['offset'] + page_number)


def set_page_number_start(section, start):
    sect_pr = section._sectPr
    for old in sect_pr.findall(qn('w:pgNumType')):
        sect_pr.remove(old)
    # Schema order: pgNumType follows pgMar/paperSrc/pgBorders/lnNumType.
    previous = next((e for e in reversed(list(sect_pr)) if e.tag in {qn('w:pgMar'), qn('w:paperSrc'), qn('w:pgBorders'), qn('w:lnNumType')}), None)
    number_type = parse_xml(f'<w:pgNumType {nsdecls("w")} w:start="{start}"/>')
    if previous is None:
        sect_pr.insert(0, number_type)
    else:
        previous.addnext(number_type)


def use_exact_line_spacing():
    """pdf2docx writes line spacing relative to the PDF font's metrics. Opened where that font is
    missing (e.g. Bookman Old Style on Linux), a substitute font changes every line height and pages
    overflow into extra ones. Exact spacing keeps the PDF line pitch whatever font is installed."""
    def parse_line_spacing(self):
        for block in self._instances:
            if block.is_text_block:
                block.line_space_type = 0
                block.parse_exact_line_spacing()

    Blocks._parse_line_spacing = parse_line_spacing


def main(source, target):
    use_exact_line_spacing()
    doc = pymupdf.open(source)
    if doc.needs_pass:
        print('El PDF está protegido con contraseña.', file=sys.stderr)
        return 3

    artifacts = repeated_artifacts(doc) + page_number_artifacts(doc)
    signatures = []
    for number, page in enumerate(doc):
        flatten_annotations(page)
        signatures += flatten_signature_boxes(page)
        for artifact in artifacts:
            if number in artifact['pages']:
                page.add_redact_annot(artifact['boxes'][number])
        page.apply_redactions(images=pymupdf.PDF_REDACT_IMAGE_REMOVE, graphics=pymupdf.PDF_REDACT_LINE_ART_NONE)

    with tempfile.TemporaryDirectory() as work:
        cleaned = str(Path(work) / 'body.pdf')
        doc.save(cleaned, garbage=0)
        converter = Converter(cleaned)
        try:
            converter.convert(target, multi_processing=False)
        finally:
            converter.close()

    if artifacts or signatures:
        word = Document(target)
        if artifacts:
            move_to_header_footer(word, pymupdf.open(source), artifacts)
        describe_pictures(word, signatures)
        word.save(target)
    return 0


if __name__ == '__main__':
    if len(sys.argv) != 3:
        print('Usage: pdf_to_docx.py <input.pdf> <output.docx>', file=sys.stderr)
        sys.exit(2)
    sys.exit(main(sys.argv[1], sys.argv[2]))
