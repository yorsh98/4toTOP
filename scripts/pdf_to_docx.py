"""PDF -> DOCX with paragraph/table reconstruction (pdf2docx), shaped like Acrobat XI output.

Usage: python3 -I pdf_to_docx.py <input.pdf> <output.docx>

Before reconstruction, content repeated at the same position on every page (logos,
QR/validation blocks) is moved to the Word header/footer as page-anchored objects,
and form/annotation appearances (e.g. signature boxes) are kept as images in place.
"""

import io
import re
import sys
import tempfile
from pathlib import Path
from xml.sax.saxutils import escape

import pymupdf
from docx import Document
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls
from docx.shared import Pt
from pdf2docx import Converter

TOLERANCE = 2.0
EMU_PER_PT = 12700
VML_NS = 'xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"'


def same_box(a, b):
    return all(abs(x - y) <= TOLERANCE for x, y in zip(a, b))


def repeated_artifacts(doc):
    """Images and text blocks drawn at the same place on every page."""
    if doc.page_count < 2:
        return [], []

    def page_images(page):
        found = []
        for info in page.get_images(full=True):
            for rect in page.get_image_rects(info[0]):
                found.append((info[0], tuple(rect)))
        return found

    def page_texts(page):
        found = []
        for block in page.get_text('dict')['blocks']:
            if block['type'] == 0:
                text = ' '.join(s['text'] for line in block['lines'] for s in line['spans']).strip()
                if text:
                    found.append((text, tuple(block['bbox']), block))
        return found

    pages = list(doc)
    images = [(xref, box) for xref, box in page_images(pages[0])
              if all(any(x == xref and same_box(box, b) for x, b in page_images(p)) for p in pages[1:])]
    others = [page_texts(p) for p in pages[1:]]
    texts = [(box, block) for text, box, block in page_texts(pages[0])
             if all(any(t == text and same_box(box, b) for t, b, _ in found) for found in others)]

    return images, texts


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


def text_box_xml(block, page_width, index):
    """A page-anchored VML text box per block (as Acrobat XI writes them); one paragraph per line, PDF line pitch."""
    x0, y0, x1, y1 = block['bbox']
    lines = block['lines']
    paragraphs = ''
    for number, line in enumerate(lines):
        pitch = (lines[number + 1]['bbox'][1] - line['bbox'][1]) if number + 1 < len(lines) else line['bbox'][3] - line['bbox'][1]
        paragraphs += (
            f'<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="{max(round(pitch * 20), 20)}" w:lineRule="exact"/>'
            f'<w:ind w:left="{round((line["bbox"][0] - x0) * 20)}" w:right="0"/></w:pPr>'
            + ''.join(run_xml(span) for span in line['spans']) + '</w:p>'
        )
    # Substituted fonts are often a little wider than the PDF ones: leave room so lines do not wrap.
    width = min((x1 - x0) * 1.1 + 2, page_width - x0)
    return (
        f'<w:r {nsdecls("w")} {VML_NS}><w:pict><v:shape id="artifact_text_{index}" type="#_x0000_t202" filled="f" stroked="f" '
        f'style="position:absolute;margin-left:{x0:.2f}pt;margin-top:{y0:.2f}pt;width:{width:.2f}pt;height:{y1 - y0:.2f}pt;'
        f'mso-position-horizontal-relative:page;mso-position-vertical-relative:page;z-index:-{index}">'
        f'<v:textbox inset="0,0,0,0" style="mso-fit-shape-to-text:t"><w:txbxContent>{paragraphs}</w:txbxContent></v:textbox></v:shape></w:pict></w:r>'
    )


def move_to_header_footer(docx_path, doc, images, texts):
    word = Document(docx_path)
    page_height = doc[0].rect.height
    section = word.sections[0]
    header, footer = section.header, section.footer
    header.is_linked_to_previous = footer.is_linked_to_previous = False
    targets = {'header': header, 'footer': footer}
    footer_top = page_height

    for index, (xref, box) in enumerate(images, start=1):
        target = targets['header' if box[1] < page_height / 2 else 'footer']
        if target is footer:
            footer_top = min(footer_top, box[1])
        rid, _ = target.part.get_or_add_image(io.BytesIO(image_png(doc, xref)))
        paragraph = target.paragraphs[0] if target.paragraphs else target.add_paragraph()
        paragraph._p.append(parse_xml(anchor_image_xml(rid, box, index)))

    for index, (box, block) in enumerate(texts, start=1):
        target = targets['header' if box[1] < page_height / 2 else 'footer']
        if target is footer:
            footer_top = min(footer_top, box[1])
        paragraph = target.paragraphs[0] if target.paragraphs else target.add_paragraph()
        paragraph._p.append(parse_xml(text_box_xml(block, doc[0].rect.width, index)))

    for part in (header, footer):
        anchor = part.paragraphs[0] if part.paragraphs else part.add_paragraph()
        anchor.paragraph_format.space_before = anchor.paragraph_format.space_after = Pt(0)
        anchor.paragraph_format.line_spacing = Pt(1)

    for each in word.sections:
        each.header_distance = each.footer_distance = Pt(min(36, max(0, page_height - footer_top) / 2))
        if footer_top < page_height:
            each.bottom_margin = Pt(max(page_height - footer_top, 0))
    word.save(docx_path)


def main(source, target):
    doc = pymupdf.open(source)
    if doc.needs_pass:
        print('El PDF está protegido con contraseña.', file=sys.stderr)
        return 3

    images, texts = repeated_artifacts(doc)
    for page in doc:
        flatten_annotations(page)
        for _, box in images:
            page.add_redact_annot(box)
        for box, _ in texts:
            page.add_redact_annot(box)
        page.apply_redactions(images=pymupdf.PDF_REDACT_IMAGE_REMOVE, graphics=pymupdf.PDF_REDACT_LINE_ART_NONE)

    with tempfile.TemporaryDirectory() as work:
        cleaned = str(Path(work) / 'body.pdf')
        doc.save(cleaned, garbage=0)
        converter = Converter(cleaned)
        try:
            converter.convert(target, multi_processing=False)
        finally:
            converter.close()

    if images or texts:
        move_to_header_footer(target, pymupdf.open(source), images, texts)
    return 0


if __name__ == '__main__':
    if len(sys.argv) != 3:
        print('Usage: pdf_to_docx.py <input.pdf> <output.docx>', file=sys.stderr)
        sys.exit(2)
    sys.exit(main(sys.argv[1], sys.argv[2]))
