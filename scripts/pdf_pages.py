"""Page operations for /convertir-archivos, keeping what is drawn over the pages.

Usage: python3 -I pdf_pages.py merge <output.pdf> <input.pdf>...
       python3 -I pdf_pages.py split <input.pdf>              plan on stdin: [{"path": ..., "ranges": [[1, 3], [5, 5]]}, ...]
       python3 -I pdf_pages.py bake <input.pdf> <output.pdf>
       python3 -I pdf_pages.py rotate <input.pdf> <output.pdf>    stdin: {"pages": [1, 3], "angle": 90}
       python3 -I pdf_pages.py organize <input.pdf> <output.pdf>  stdin: [{"page": 2, "rotation": 90}, ...]
       python3 -I pdf_pages.py number <input.pdf> <output.pdf>    stdin: see number()
       python3 -I pdf_pages.py images                             stdin: see images()

Signature fields and annotations are not part of the page content: Poppler's pdfseparate drops them and
pdfunite drops links. Their appearance is baked into the page first, so an e-signature box stays visible
(its cryptographic validity is lost anyway once pages are rewritten), and links are copied with the pages.
Prints {"flattened": n}, the number of annotations and fields baked, on stdout.
"""

import json
import sys

import pymupdf

# Visible page sizes in points; "oficio" is the Chilean 216 x 330 mm used by the courts.
PAGE_SIZES = {'a4': (595, 842), 'letter': (612, 792), 'oficio': (612, 936)}
IMAGE_MARGINS = {'none': 0, 'small': 20, 'large': 50}
NUMBER_MARGINS = {'small': 18, 'medium': 36, 'large': 54}


def open_baked(path):
    doc = pymupdf.open(path)
    if doc.needs_pass:
        print(f'{path}: El PDF está protegido con contraseña.', file=sys.stderr)
        sys.exit(3)
    flattened = sum(len(list(page.annots())) + len(list(page.widgets())) for page in doc)
    if flattened:
        doc.bake(annots=True, widgets=True)
    return doc, flattened


def save(doc, path):
    doc.save(path, garbage=3, deflate=True)


def merge(output, inputs):
    result = pymupdf.open()
    flattened = 0
    for path in inputs:
        doc, count = open_baked(path)
        flattened += count
        result.insert_pdf(doc)
    save(result, output)
    return flattened


def split(source, plan):
    doc, flattened = open_baked(source)
    for part in plan:
        result = pymupdf.open()
        for first, last in part['ranges']:
            result.insert_pdf(doc, from_page=first - 1, to_page=last - 1)
        save(result, part['path'])
    return flattened


def bake(source, output):
    doc, flattened = open_baked(source)
    save(doc, output)
    return flattened


def rotate(source, output, spec):
    doc, flattened = open_baked(source)
    for number in spec['pages']:
        page = doc[number - 1]
        page.set_rotation((page.rotation + spec['angle']) % 360)
    save(doc, output)
    return flattened


def organize(source, output, plan):
    doc, flattened = open_baked(source)
    doc.select([item['page'] - 1 for item in plan])
    for page, item in zip(doc, plan):
        page.set_rotation((page.rotation + item['rotation']) % 360)
    save(doc, output)
    return flattened


def number(source, output, spec):
    """spec: position ("top|bottom-left|center|right"), margin (small|medium|large), format with {n} and {total},
    start (first number), first/last (1-based pages to number) and size (points)."""
    doc, flattened = open_baked(source)
    vertical, horizontal = spec['position'].split('-')
    margin = NUMBER_MARGINS[spec['margin']]
    size = spec['size']
    first, last = spec['first'], spec['last']
    total = spec['start'] + last - first
    for index in range(first - 1, last):
        page = doc[index]
        text = spec['format'].replace('{n}', str(spec['start'] + index - first + 1)).replace('{total}', str(total))
        width = pymupdf.get_text_length(text, fontname='helv', fontsize=size)
        # Positions are worked out on the page as it is seen (page.rect is rotated) and mapped back to the
        # unrotated coordinates insert_text expects, so numbers stay upright on rotated scans.
        x = {'left': margin, 'center': (page.rect.width - width) / 2, 'right': page.rect.width - margin - width}[horizontal]
        y = margin + size if vertical == 'top' else page.rect.height - margin
        page.insert_text(pymupdf.Point(x, y) * page.derotation_matrix, text, fontname='helv', fontsize=size, rotate=page.rotation)
    save(doc, output)
    return flattened


def images(spec):
    """spec: parts ([{"path": out.pdf, "images": [paths]}]), page_size (fit|a4|letter|oficio),
    orientation (portrait|landscape) and margin (none|small|large). JPEGs are embedded without recompression."""
    from PIL import Image, ImageOps

    margin = IMAGE_MARGINS[spec['margin']]
    for part in spec['parts']:
        result = pymupdf.open()
        for path in part['images']:
            with Image.open(path) as image:
                width, height = image.size
                # Phone photos store their orientation in EXIF; the PDF needs the pixels upright.
                if image.getexif().get(0x0112, 1) != 1:
                    upright = ImageOps.exif_transpose(image)
                    width, height = upright.size
                    path = path + '.upright.' + ('png' if image.format == 'PNG' else 'jpg')
                    (upright if image.format == 'PNG' else upright.convert('RGB')).save(path, quality=95)
            if spec['page_size'] == 'fit':
                scale = 842 / max(width, height)
                page_width, page_height = width * scale + 2 * margin, height * scale + 2 * margin
            else:
                page_width, page_height = PAGE_SIZES[spec['page_size']]
                if spec['orientation'] == 'landscape':
                    page_width, page_height = page_height, page_width
            page = result.new_page(width=page_width, height=page_height)
            page.insert_image(pymupdf.Rect(margin, margin, page_width - margin, page_height - margin), filename=path, keep_proportion=True)
        save(result, part['path'])
    return 0


if __name__ == '__main__':
    command, arguments = (sys.argv[1], sys.argv[2:]) if len(sys.argv) > 1 else (None, [])
    if command == 'merge' and len(arguments) >= 2:
        flattened = merge(arguments[0], arguments[1:])
    elif command == 'split' and len(arguments) == 1:
        flattened = split(arguments[0], json.load(sys.stdin))
    elif command == 'bake' and len(arguments) == 2:
        flattened = bake(*arguments)
    elif command in ('rotate', 'organize', 'number') and len(arguments) == 2:
        flattened = {'rotate': rotate, 'organize': organize, 'number': number}[command](*arguments, json.load(sys.stdin))
    elif command == 'images' and not arguments:
        flattened = images(json.load(sys.stdin))
    else:
        print(__doc__, file=sys.stderr)
        sys.exit(2)
    print(json.dumps({'flattened': flattened}))
