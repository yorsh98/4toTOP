<?php

use App\Conversions\ConversionTool;
use App\Models\FileConversion;
use App\Services\FileConversionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/*
 * Fixtures and probes shared by the /convertir-archivos tests (FileConversionTest, PdfToolsTest).
 */

function conversionPdf(string $content, bool $styled = false): string
{
    return conversionPdfPages([$content === '' ? '' : ($styled
        ? 'BT /F2 18 Tf 72 750 Td ('.$content.') Tj ET BT /F1 12 Tf 72 720 Td (Texto editable) Tj ET'
        : 'BT /F1 12 Tf 72 720 Td ('.$content.') Tj ET')]);
}

/** One content stream per page; /F1 is Helvetica, /F2 Helvetica-Bold and /Im1 a grey image (scan stand-in). Pages are Letter unless $heights gives another height in points. */
function conversionPdfPages(array $streams, array $heights = []): string
{
    $count = count($streams);
    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids ['.implode(' ', array_map(fn ($i) => (6 + 2 * $i).' 0 R', array_keys($streams))).'] /Count '.$count.' >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        "<< /Type /XObject /Subtype /Image /Width 1 /Height 1 /ColorSpace /DeviceGray /BitsPerComponent 8 /Length 1 >>\nstream\n\x80\nendstream",
    ];
    foreach ($streams as $i => $stream) {
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 '.($heights[$i] ?? 792).'] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << /Im1 5 0 R >> >> /Contents '.(7 + 2 * $i).' 0 R >>';
        $objects[] = '<< /Length '.strlen($stream).' >>'."\nstream\n{$stream}\nendstream";
    }
    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
    }

    $start = strlen($pdf);
    $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
    foreach (array_slice($offsets, 1) as $offset) {
        $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
    }

    return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$start}\n%%EOF\n";
}

function queuedPdfConversion(string $pdf, string $name = 'acta'): FileConversion
{
    $token = (string) Str::uuid();
    $job = FileConversion::create([
        'token' => $token, 'area' => 'frontoffice', 'format' => 'pdf',
        'status' => 'queued', 'original_name' => "{$name}.pdf", 'result_name' => "{$name}.docx",
        'input_path' => "conversions/{$token}/original.pdf", 'actor_name' => 'Ana',
        'ip_address' => '127.0.0.1', 'session_hash' => hash('sha256', 'test'),
    ]);
    Storage::disk('local')->put($job->input_path, $pdf);

    return $job;
}

function docxPart(FileConversion $job, string $part): string|false
{
    $document = new ZipArchive;
    $document->open(Storage::disk('local')->path($job->output_path));
    $xml = $document->getFromName($part);
    $document->close();

    return $xml;
}

/** An executable shell script, for standing in for an external tool. */
function fakeTool(string $body): string
{
    $path = tempnam(sys_get_temp_dir(), 'conversion-tool-');
    file_put_contents($path, "#!/bin/sh\n{$body}\n");
    chmod($path, 0755);

    return $path;
}

function pdf2docxAvailable(): bool
{
    $process = new Process([config('conversions.python'), '-I', '-c', 'import pdf2docx']);
    $process->run();

    return $process->isSuccessful();
}

/** All header and footer parts of a converted DOCX. */
function docxHeaderFooterParts(FileConversion $job): array
{
    $document = new ZipArchive;
    $document->open(Storage::disk('local')->path($job->output_path));
    $parts = [];
    for ($i = 0; $i < $document->numFiles; $i++) {
        $name = $document->getNameIndex($i);
        if (preg_match('#^word/(header|footer)\d+\.xml$#', $name)) {
            $parts[$name] = $document->getFromName($name);
        }
    }
    $document->close();

    return $parts;
}

function pymupdfAvailable(): bool
{
    $process = new Process([config('conversions.python'), '-I', '-c', 'import pymupdf']);
    $process->run();

    return $process->isSuccessful();
}

/** Two pages, "S1" and "S2"; page 2 carries a filled form field ("Firma Juez") and a mailto link, both as annotations. */
function pdfWithFieldAndLink(): string
{
    $path = tempnam(sys_get_temp_dir(), 'signed-pdf-');
    (new Process([config('conversions.python'), '-I', '-c', <<<'PY'
import sys, pymupdf
doc = pymupdf.open()
for text in ('S1', 'S2'):
    doc.new_page().insert_text((72, 72), text)
page = doc[1]
field = pymupdf.Widget()
field.field_type = pymupdf.PDF_WIDGET_TYPE_TEXT
field.field_name = 'Firma1'
field.field_value = 'Firma Juez'
field.rect = pymupdf.Rect(72, 200, 300, 230)
page.add_widget(field)
page.insert_link({'kind': pymupdf.LINK_URI, 'from': pymupdf.Rect(72, 300, 200, 320), 'uri': 'mailto:fiscal@example.cl'})
doc.save(sys.argv[1])
PY, $path]))->mustRun();
    $pdf = file_get_contents($path);
    unlink($path);

    return $pdf;
}

/** @return list<string> */
function pdfLinks(string $path): array
{
    $process = new Process([config('conversions.python'), '-I', '-c', 'import sys, pymupdf; print("\\n".join(l["uri"] for p in pymupdf.open(sys.argv[1]) for l in p.get_links() if l.get("uri")))', $path]);
    $process->mustRun();

    return array_values(array_filter(explode("\n", trim($process->getOutput()))));
}

/** A PDF whose pages read "<prefix> 1", "<prefix> 2"… so merged and split output can be traced back. */
function numberedPdf(string $prefix, int $pages): string
{
    return conversionPdfPages(array_map(fn (int $page) => "BT /F1 12 Tf 72 720 Td ({$prefix} {$page}) Tj ET", range(1, $pages)));
}

/** @return list<string> the text of each page */
function pdfPageTexts(string $path): array
{
    $process = new Process([config('conversions.pdftotext'), $path, '-']);
    $process->mustRun();

    // Whitespace is dropped: pdftotext does not always keep the space between the prefix and the number.
    return array_values(array_filter(array_map(fn (string $page) => preg_replace('/\s+/', '', $page), explode("\f", $process->getOutput())), fn (string $page) => $page !== ''));
}

/** A queued job of any tool, stored as FileConversionService stores it; $files maps original names to contents. */
function queuedPdfToolJob(string $format, array $options, array $files): FileConversion
{
    $tool = ConversionTool::for($format);
    $token = (string) Str::uuid();
    $inputs = [];
    foreach (array_keys($files) as $index => $name) {
        $path = "conversions/{$token}/".($tool->multiple() ? sprintf('input-%02d', $index + 1) : 'original').'.'.pathinfo($name, PATHINFO_EXTENSION);
        Storage::disk('local')->put($path, $files[$name]);
        $inputs[] = ['path' => $path, 'name' => $name];
    }

    return FileConversion::create([
        'token' => $token, 'area' => 'frontoffice', 'format' => $format, 'status' => 'queued',
        'original_name' => array_key_first($files),
        'result_name' => pathinfo(array_key_first($files), PATHINFO_FILENAME).$tool->resultSuffix().'.'.$tool->resultExtension(),
        'input_path' => $inputs[0]['path'], 'options' => $tool->multiple() ? $options + ['inputs' => $inputs] : $options,
        'actor_name' => 'Ana', 'ip_address' => '127.0.0.1', 'session_hash' => hash('sha256', 'test'),
    ]);
}

/** @return array<string, list<string>> page texts of each PDF inside the ZIP, by entry name */
function zipPdfTexts(FileConversion $job): array
{
    $archive = new ZipArchive;
    $archive->open(Storage::disk('local')->path($job->output_path));
    $texts = [];
    for ($i = 0; $i < $archive->numFiles; $i++) {
        $name = $archive->getNameIndex($i);
        $path = tempnam(sys_get_temp_dir(), 'split-part-');
        file_put_contents($path, $archive->getFromIndex($i));
        $texts[$name] = pdfPageTexts($path);
        unlink($path);
    }
    $archive->close();

    return $texts;
}

/** Runs a Python snippet with PyMuPDF/Pillow that writes a fixture to sys.argv[1], and returns its bytes. */
function pythonFixture(string $script): string
{
    $path = tempnam(sys_get_temp_dir(), 'conversion-fixture-');
    (new Process([config('conversions.python'), '-I', '-c', "import sys, pymupdf\n".$script, $path]))->mustRun();
    $content = file_get_contents($path);
    unlink($path);

    return $content;
}

function ocrAvailable(): bool
{
    $process = new Process(['tesseract', '--list-langs']);
    $process->run();

    return pymupdfAvailable() && is_executable(dirname(config('conversions.python')).'/ocrmypdf') && str_contains($process->getOutput(), 'spa');
}

/** Page 1 is a picture of text (a scan); page 2 has real text. */
function scannedPdf(): string
{
    return pythonFixture(<<<'PY'
source = pymupdf.open()
source.new_page().insert_text((72, 100), 'Acta del tribunal de garantia', fontsize=24)
scan = pymupdf.open()
page = scan.new_page()
page.insert_image(page.rect, pixmap=source[0].get_pixmap(dpi=200))
scan.new_page().insert_text((72, 100), 'Texto digital', fontsize=24)
scan.save(sys.argv[1])
PY);
}

/** One page with real text over a 600 dpi picture, heavy enough for Ghostscript to shrink. */
function heavyPdf(): string
{
    return pythonFixture(<<<'PY'
source = pymupdf.open()
source.new_page().insert_text((72, 100), 'Fondo escaneado', fontsize=24)
doc = pymupdf.open()
page = doc.new_page()
page.insert_image(page.rect, pixmap=source[0].get_pixmap(dpi=600))
page.insert_text((72, 300), 'Comprimir', fontsize=24)
doc.save(sys.argv[1])
PY);
}

/** A 200 x 100 JPEG whose EXIF says "rotate 90°", as phones store portrait photos. */
function sidewaysPhoto(): string
{
    return pythonFixture(<<<'PY'
from PIL import Image
image = Image.new('RGB', (200, 100), 'navy')
exif = image.getexif()
exif[0x0112] = 6
image.save(sys.argv[1], 'JPEG', exif=exif)
PY);
}

function squarePng(): string
{
    return pythonFixture(<<<'PY'
from PIL import Image
Image.new('RGB', (100, 100), 'white').save(sys.argv[1], 'PNG')
PY);
}

/** @return list<array{float, float, int}> width, height (as seen) and rotation of each page */
function pdfPageGeometry(string $path): array
{
    $process = new Process([config('conversions.python'), '-I', '-c', 'import sys, json, pymupdf; print(json.dumps([[round(p.rect.width), round(p.rect.height), p.rotation] for p in pymupdf.open(sys.argv[1])]))', $path]);
    $process->mustRun();

    return json_decode($process->getOutput(), true);
}
