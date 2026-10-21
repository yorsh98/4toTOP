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
from docx.oxml.ns import nsdecls
from docx.shared import Pt
from pdf2docx import Converter
from pdf2docx.layout.Blocks import Blocks

TOLERANCE = 2.0
BAND = 0.2  # share of the page height, at top and bottom, where letterheads and footers live
EMU_PER_PT = 12700
VML_NS = 'xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"'


def same_box(a, b):
    return all(abs(x - y) <= TOLERANCE for x, y in zip(a, b))


def repeated_artifacts(doc):
    """Images and text blocks drawn at the same place in the top/bottom band of most pages.

    Documents often omit the letterhead on some pages (e.g. a final signature page), so an
    artifact only needs to repeat on at least two pages and half of them. Each artifact
    records the pages it is on: those pages get it in their header/footer, the others do not.
    """
    if doc.page_count < 2:
        return []

    artifacts = []
    for number, page in enumerate(doc):
        band = page.rect.height * BAND
        found = [('image', info[0], tuple(rect), None)
                 for info in page.get_images(full=True) for rect in page.get_image_rects(info[0])]
        for block in page.get_text('dict')['blocks']:
            if block['type'] == 0:
                text = ' '.join(s['text'] for line in block['lines'] for s in line['spans']).strip()
                if text:
                    found.append(('text', text, tuple(block['bbox']), block))

        for kind, key, box, block in found:
            if box[3] > band and box[1] < page.rect.height - band:
                continue
            match = next((a for a in artifacts if a['kind'] == kind and a['key'] == key and same_box(a['box'], box)), None)
            if match is None:
                artifacts.append({'kind': kind, 'key': key, 'box': box, 'block': block, 'pages': {number}})
            else:
                match['pages'].add(number)

    return [a for a in artifacts if len(a['pages']) >= 2 and len(a['pages']) * 2 >= doc.page_count]


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


def fill_header_footer(section, doc, artifacts, page, counter):
    """Writes this page's artifacts into the section's own header/footer; returns the footer top."""
    header, footer = section.header, section.footer
    header.is_linked_to_previous = footer.is_linked_to_previous = False
    footer_top = page.rect.height

    for artifact in artifacts:
        box = artifact['box']
        target = header if box[1] < page.rect.height / 2 else footer
        if target is footer:
            footer_top = min(footer_top, box[1])
        paragraph = target.paragraphs[0] if target.paragraphs else target.add_paragraph()
        index = next(counter)
        if artifact['kind'] == 'image':
            rid, _ = target.part.get_or_add_image(io.BytesIO(image_png(doc, artifact['key'])))
            paragraph._p.append(parse_xml(anchor_image_xml(rid, box, index)))
        else:
            paragraph._p.append(parse_xml(text_box_xml(artifact['block'], page.rect.width, index)))

    for part in (header, footer):
        anchor = part.paragraphs[0] if part.paragraphs else part.add_paragraph()
        anchor.paragraph_format.space_before = anchor.paragraph_format.space_after = Pt(0)
        anchor.paragraph_format.line_spacing = Pt(1)

    return footer_top


def move_to_header_footer(docx_path, doc, artifacts):
    """Each page gets exactly the artifacts it had; consecutive pages with the same set share one header/footer."""
    word = Document(docx_path)
    counter = itertools.count(1)
    page_number, previous, footer_top = -1, None, None

    for index, section in enumerate(word.sections):
        # pdf2docx starts every page with a new-page section; column layouts add continuous ones.
        if index == 0 or section.start_type != WD_SECTION_START.CONTINUOUS:
            page_number = min(page_number + 1, doc.page_count - 1)
        page = doc[page_number]
        present = frozenset(i for i, a in enumerate(artifacts) if page_number in a['pages'])

        if index > 0 and present == previous:
            section.header.is_linked_to_previous = section.footer.is_linked_to_previous = True
        else:
            footer_top = fill_header_footer(section, doc, [artifacts[i] for i in sorted(present)], page, counter)
            previous = present

        free_bottom = page.rect.height - footer_top
        section.footer_distance = Pt(min(36, max(0, free_bottom) / 2))
        if free_bottom > 0:
            section.bottom_margin = Pt(free_bottom)
        # Word and LibreOffice push the body below the header paragraph: keep it inside the top margin.
        section.header_distance = Pt(max(0, min(36, section.top_margin.pt - 2)))

    word.save(docx_path)


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

    artifacts = repeated_artifacts(doc)
    for number, page in enumerate(doc):
        flatten_annotations(page)
        for artifact in artifacts:
            if number in artifact['pages']:
                page.add_redact_annot(artifact['box'])
        page.apply_redactions(images=pymupdf.PDF_REDACT_IMAGE_REMOVE, graphics=pymupdf.PDF_REDACT_LINE_ART_NONE)

    with tempfile.TemporaryDirectory() as work:
        cleaned = str(Path(work) / 'body.pdf')
        doc.save(cleaned, garbage=0)
        converter = Converter(cleaned)
        try:
            converter.convert(target, multi_processing=False)
        finally:
            converter.close()

    if artifacts:
        move_to_header_footer(target, pymupdf.open(source), artifacts)
    return 0


if __name__ == '__main__':
    if len(sys.argv) != 3:
        print('Usage: pdf_to_docx.py <input.pdf> <output.docx>', file=sys.stderr)
        sys.exit(2)
    sys.exit(main(sys.argv[1], sys.argv[2]))
