<?php

use App\Conversions\OcrPdfTool;
use App\Conversions\PdfToWordTool;
use App\Jobs\ConvertFile;
use App\Livewire\PublicFileConverterPage;
use App\Models\FileConversion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\Process\Process;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

function enableOcr(): void
{
    config()->set('conversions.ocrmypdf', dirname(config('conversions.python')).'/ocrmypdf');
}

it('groups the tools like iLovePDF and opens the one in the URL', function () {
    enableOcr();

    Livewire::withQueryParams(['herramienta' => 'pagenum'])->test(PublicFileConverterPage::class)
        ->assertSet('format', 'pagenum')
        ->assertSee(['Convertir', 'Organizar', 'Optimizar', 'Editar', 'Comprimir PDF', 'OCR: PDF con texto buscable', 'Organizar PDF'])
        ->call('selectFormat', 'compress')
        ->assertSet('format', 'compress')
        ->assertSet('options', ['level' => 'recommended']);
});

it('hides OCR when OCRmyPDF is not configured', function () {
    config()->set('conversions.ocrmypdf', null);

    Livewire::withQueryParams(['herramienta' => 'ocr'])->test(PublicFileConverterPage::class)
        ->assertSet('format', 'pdf')
        ->assertDontSee('OCR: PDF con texto buscable')
        ->call('selectFormat', 'ocr')
        ->assertSet('format', 'pdf');
});

it('makes a scanned PDF searchable and keeps pages that already had text', function () {
    enableOcr();
    $job = queuedPdfToolJob('ocr', ['language' => 'spa', 'redo' => false, 'deskew' => true, 'pdfa' => false], ['escaneo.pdf' => scannedPdf()]);

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    $texts = pdfPageTexts(Storage::disk('local')->path($job->output_path));
    expect($job->status)->toBe('ready')
        ->and($job->engine)->toBe('ocrmypdf')
        ->and($job->result_name)->toBe('escaneo_ocr.pdf')
        ->and($texts[0])->toContain('tribunal')
        ->and($texts[1])->toBe('Textodigital')
        ->and($job->notice)->toContain('Se reconoció texto en la página 1.');
})->skip(fn () => ! ocrAvailable(), 'OCRmyPDF, Tesseract (spa) or PyMuPDF are not installed for CONVERSIONS_PYTHON');

it('rejects a PDF that needs no OCR without using up the quota, and gives OCR jobs a longer timeout', function () {
    enableOcr();
    config()->set('conversions.ocr_timeout', 1000);
    $page = Livewire::test(PublicFileConverterPage::class)
        ->call('selectFormat', 'ocr')
        ->set('actorName', 'Ana')
        ->set('file', UploadedFile::fake()->createWithContent('digital.pdf', numberedPdf('P', 2)))
        ->call('convert')
        ->assertHasErrors(['file']);

    expect(FileConversion::count())->toBe(0);

    $page->set('options.redo', true)->call('convert')->assertHasNoErrors();

    expect(FileConversion::sole()->options)->toBe(['language' => 'spa', 'redo' => true, 'deskew' => true, 'pdfa' => false]);
    Queue::assertPushed(ConvertFile::class, fn (ConvertFile $job) => $job->timeout === OcrPdfTool::ocrTimeout() && $job->timeout === 1120);
});

it('limits OCR to the configured number of pages', function () {
    enableOcr();
    config()->set('conversions.ocr_max_pages', 1);

    Livewire::test(PublicFileConverterPage::class)
        ->call('selectFormat', 'ocr')
        ->set('actorName', 'Ana')
        ->set('file', UploadedFile::fake()->createWithContent('largo.pdf', numberedPdf('P', 2)))
        ->set('options.redo', true)
        ->call('convert')
        ->assertHasErrors(['file']);
});

it('compresses a heavy PDF keeping its text, and returns an optimized one unchanged', function () {
    $heavy = queuedPdfToolJob('compress', ['level' => 'extreme'], ['pesado.pdf' => heavyPdf()]);
    (new ConvertFile($heavy->id))->handle();
    $heavy->refresh();

    expect($heavy->status)->toBe('ready')
        ->and($heavy->result_name)->toBe('pesado_comprimido.pdf')
        ->and(Storage::disk('local')->size($heavy->output_path))->toBeLessThan(Storage::disk('local')->size($heavy->input_path) * 0.95)
        ->and(pdfPageTexts(Storage::disk('local')->path($heavy->output_path))[0])->toContain('Comprimir')
        ->and($heavy->notice)->toContain('Pasó de');

    $small = queuedPdfToolJob('compress', ['level' => 'recommended'], ['liviano.pdf' => numberedPdf('P', 1)]);
    (new ConvertFile($small->id))->handle();
    $small->refresh();

    expect($small->notice)->toContain('ya estaba optimizado')
        ->and($small->output_sha256)->toBe(hash('sha256', Storage::disk('local')->get($small->input_path)));
})->skip(fn () => ! pymupdfAvailable(), 'PyMuPDF is not installed for CONVERSIONS_PYTHON');

it('converts a Word-processor document to PDF with LibreOffice', function () {
    $job = queuedPdfToolJob('word2pdf', [], ['resolucion.rtf' => '{\rtf1\ansi Resolucion del tribunal\par}']);

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    expect($job->status)->toBe('ready')
        ->and($job->engine)->toBe('libreoffice')
        ->and($job->result_name)->toBe('resolucion.pdf')
        ->and(pdfPageTexts(Storage::disk('local')->path($job->output_path)))->toBe(['Resoluciondeltribunal']);
});

it('turns photos into a PDF, upright and on the chosen page size', function () {
    $together = queuedPdfToolJob('img2pdf', ['page_size' => 'fit', 'orientation' => 'portrait', 'margin' => 'none', 'merge' => true, 'ocr' => false], ['foto.jpg' => sidewaysPhoto(), 'firma.png' => squarePng()]);
    (new ConvertFile($together->id))->handle();
    $together->refresh();

    // The 200 x 100 photo is stored sideways: upright it is 100 x 200, so its page is portrait.
    [$photo, $square] = pdfPageGeometry(Storage::disk('local')->path($together->output_path));
    expect($together->status)->toBe('ready')
        ->and($together->result_name)->toBe('foto.pdf')
        ->and($photo[1])->toBeGreaterThan($photo[0])
        ->and($square[0])->toBe($square[1]);

    $apart = queuedPdfToolJob('img2pdf', ['page_size' => 'oficio', 'orientation' => 'landscape', 'margin' => 'small', 'merge' => false, 'ocr' => false], ['foto.jpg' => sidewaysPhoto(), 'firma.png' => squarePng()]);
    (new ConvertFile($apart->id))->handle();
    $apart->refresh();

    expect($apart->result_name)->toBe('foto.zip')
        ->and(array_keys(zipPdfTexts($apart)))->toBe(['01_foto.pdf', '02_firma.pdf']);
})->skip(fn () => ! pymupdfAvailable(), 'PyMuPDF and Pillow are not installed for CONVERSIONS_PYTHON');

it('numbers pages from the chosen page, with the total', function () {
    $job = queuedPdfToolJob('pagenum', ['position' => 'bottom-center', 'margin' => 'medium', 'format' => 'page_total', 'start' => 1, 'first' => 2, 'last' => 3, 'size' => 11], ['expediente.pdf' => numberedPdf('P', 3)]);

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    expect($job->status)->toBe('ready')
        ->and($job->result_name)->toBe('expediente_numerado.pdf')
        ->and(pdfPageTexts(Storage::disk('local')->path($job->output_path)))->toBe(['P1', 'P2Página1de2', 'P3Página2de2']);
})->skip(fn () => ! pymupdfAvailable(), 'PyMuPDF is not installed for CONVERSIONS_PYTHON');

it('checks the page-number range against the PDF', function () {
    Livewire::test(PublicFileConverterPage::class)
        ->call('selectFormat', 'pagenum')
        ->set('actorName', 'Ana')
        ->set('file', UploadedFile::fake()->createWithContent('acta.pdf', numberedPdf('P', 3)))
        ->set('options.last', 9)
        ->call('convert')
        ->assertHasErrors(['options.last']);
});

it('rotates only the selected pages', function () {
    $job = queuedPdfToolJob('rotate', ['scope' => 'select', 'ranges' => '2', 'angle' => 90], ['acta.pdf' => numberedPdf('P', 3)]);

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    expect($job->status)->toBe('ready')
        ->and(array_column(pdfPageGeometry(Storage::disk('local')->path($job->output_path)), 2))->toBe([0, 90, 0]);
})->skip(fn () => ! pymupdfAvailable(), 'PyMuPDF is not installed for CONVERSIONS_PYTHON');

it('reorders, rotates and removes pages as planned in the browser', function () {
    Livewire::test(PublicFileConverterPage::class)
        ->call('selectFormat', 'organize')
        ->set('actorName', 'Ana')
        ->set('file', UploadedFile::fake()->createWithContent('acta.pdf', numberedPdf('P', 3)))
        ->set('options.plan', json_encode([['page' => 9, 'rotation' => 0]]))
        ->call('convert')
        ->assertHasErrors(['options.plan'])
        ->set('options.plan', json_encode([['page' => 3, 'rotation' => 0], ['page' => 1, 'rotation' => 180]]))
        ->call('convert')
        ->assertHasNoErrors();

    $job = FileConversion::sole();
    expect($job->options['plan'])->toBe([['page' => 3, 'rotation' => 0], ['page' => 1, 'rotation' => 180]]);

    if (pymupdfAvailable()) {
        (new ConvertFile($job->id))->handle();
        $output = Storage::disk('local')->path($job->fresh()->output_path);
        expect(pdfPageTexts($output))->toBe(['P3', 'P1'])
            ->and(array_column(pdfPageGeometry($output), 2))->toBe([0, 180]);
    }
});

it('reports which conversion program is missing', function () {
    config()->set('conversions.python', '/does-not-exist/python');
    config()->set('conversions.ocrmypdf', null);

    $this->artisan('conversions:check')
        ->expectsOutputToContain('Python (PyMuPDF, pdf2docx, Pillow)')
        ->expectsOutputToContain('CONVERSIONS_OCRMYPDF vacío')
        ->expectsOutputToContain('Hay componentes con problemas')
        ->assertFailed();
});

it('passes the server check when OCR and the queue are set up', function () {
    enableOcr();
    config()->set('conversions.python', dirname(config('conversions.python')).'/python');
    // Programs this machine may not have (FFmpeg, LibreOffice) are replaced by ones that exist.
    foreach (['ffprobe', 'libreoffice', 'ghostscript', 'pdfinfo', 'pdftotext', 'pdfimages'] as $program) {
        config()->set("conversions.{$program}", fakeTool('echo ok'));
    }
    config()->set('conversions.ffmpeg', fakeTool('echo "libmp3lame aac pcm_s16le libvorbis flac "'));
    config()->set('conversions.pdftotext', 'pdftotext');
    config()->set('queue.connections.'.config('queue.default').'.retry_after', 2100);

    $this->artisan('conversions:check')
        ->expectsOutputToContain('Texto reconocido: Tribunal')
        ->expectsOutputToContain('Todo listo')
        ->assertSuccessful();
})->skip(fn () => ! ocrAvailable(), 'OCRmyPDF, Tesseract (spa) or PyMuPDF are not installed for CONVERSIONS_PYTHON');

it('rebuilds a scanned page with visible text and an image', function () {
    $input = tempnam(sys_get_temp_dir(), 'scan');
    $output = $input.'.pdf';
    $fixture = new Process([config('conversions.python'), '-I', '-c', <<<'PYCODE'
from PIL import Image, ImageDraw, ImageFont
import sys
image = Image.new('RGB', (1700, 2200), 'white')
draw = ImageDraw.Draw(image)
font = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 48)
draw.text((180, 250), 'Documento del tribunal dicta sentencia', font=font, fill='black')
draw.text((180, 330), 'firme sobre la causa penal pública', font=font, fill='black')
draw.line([(250, 1400), (400, 1300), (310, 1450), (540, 1320), (620, 1410)], fill='black', width=14)
image.save(sys.argv[1], format='PDF', resolution=200)
PYCODE, $input]);
    $fixture->mustRun();
    $process = new Process([config('conversions.python'), base_path('scripts/rebuild_scanned_pdf.py'), $input, $output, '--lang', 'spa']);
    $process->setEnv(['PYTHONUTF8' => '0', 'LC_ALL' => 'C', 'LANG' => 'C']);
    $process->setTimeout(120);
    $process->run();
    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput())
        ->and($process->getOutput())->toContain('SUMMARY ')
        ->and(pdfPageTexts($output)[0])->toContain('Documento', 'tribunal', 'dicta', 'sentencia', 'firme', 'sobre', 'causa', 'penal', 'pública');
    $images = new Process([config('conversions.pdfimages'), '-list', $output]);
    $images->run();
    expect(preg_match_all('/^\s*\d+\s+\d+\s+image\s/m', $images->getOutput()))->toBe(1);
    unlink($input);
    unlink($output);
})->skip(fn () => ! ocrAvailable(), 'OCR no disponible');

it('queues a forced transcription using the original file and enforces ownership and limits', function () {
    enableOcr();
    $page = Livewire::test(PublicFileConverterPage::class)->set('actorName', 'Ana')
        ->set('file', UploadedFile::fake()->createWithContent('acta.pdf', numberedPdf('Texto', 1)))
        ->call('convert')->assertHasNoErrors();
    $original = FileConversion::firstOrFail();
    $original->update(['status' => 'failed']);
    $page->call('transcribeWithOcr', $original->token)->assertHasNoErrors();
    $copy = FileConversion::latest('id')->first();
    expect($copy->options)->toBe(['ocr' => 'force'])
        ->and(Storage::disk('local')->get($copy->input_path))->toBe(Storage::disk('local')->get($original->input_path));
    $copy->update(['status' => 'ready']);
    $original->update(['status' => 'ready']);
    $page->call('transcribeWithOcr', $copy->token)->assertHasErrors('file');
    Storage::disk('local')->delete($original->input_path);
    $page->call('transcribeWithOcr', $original->token)->assertHasErrors('file');
    $original->update(['session_hash' => hash('sha256', 'another')]);
    $page->call('transcribeWithOcr', $original->token)->assertNotFound();
});

it('stores the forced OCR option and displays queue and processing progress', function () {
    enableOcr();
    $page = Livewire::test(PublicFileConverterPage::class)
        ->set('actorName', 'Ana')
        ->set('options.ocr', 'force')
        ->set('file', UploadedFile::fake()->createWithContent('acta.pdf', numberedPdf('Texto', 1)))
        ->call('convert')->assertHasNoErrors();
    $first = FileConversion::firstOrFail();
    expect($first->options)->toBe(['ocr' => 'force']);
    $page->set('actorName', 'Ana')->set('file', UploadedFile::fake()->createWithContent('otra.pdf', numberedPdf('Texto', 1)))
        ->call('convert')->assertHasNoErrors()->assertSee('archivo(s) antes');
    $first->update(['status' => 'processing', 'stage' => 'Analizando el PDF', 'progress' => 5]);
    $page->call('$refresh')->assertSee('Analizando el PDF')->assertSee('aria-valuenow="5"', false);
});

it('leaves a digital color graphic unchanged and does not invoke OCR without candidates', function () {
    $directory = sys_get_temp_dir().'/pdf-digital-'.bin2hex(random_bytes(4));
    mkdir($directory);
    try {
        $fixture = new Process([config('conversions.python'), '-I', '-c', <<<'PY'
import io, sys, pymupdf
from PIL import Image, ImageDraw
image = Image.new('RGB', (600, 400), 'navy')
draw = ImageDraw.Draw(image)
draw.rectangle((50, 50, 350, 300), fill='red')
data = io.BytesIO()
image.save(data, format='PNG')
doc = pymupdf.open()
p = doc.new_page()
p.insert_text((50, 50), 'Texto digital visible')
p.insert_image(pymupdf.Rect(60, 100, 460, 400), stream=data.getvalue())
doc.new_page().insert_text((50, 50), 'Solo texto')
doc.save(sys.argv[1])
PY, $directory.'/input.pdf']);
        $fixture->mustRun();
        $process = new Process([config('conversions.python'), '-I', base_path('scripts/rebuild_scanned_pdf.py'), $directory.'/input.pdf', $directory.'/output.pdf', '--tesseract', $directory.'/missing']);
        $process->mustRun();
        expect($process->getOutput())->toContain('"pages_rebuilt": []');
        $compare = new Process([config('conversions.python'), '-I', '-c', <<<'PY'
import sys, pymupdf
a, b = (pymupdf.open(p) for p in sys.argv[1:])
for doc in (a, b):
    page = doc[0]
    image = page.get_images()[0]
    print(doc.extract_image(image[0])['image'].hex(), page.get_image_rects(image))
PY, $directory.'/input.pdf', $directory.'/output.pdf']);
        $compare->mustRun();
        $lines = explode("\n", trim($compare->getOutput()));
        expect($lines[0])->toBe($lines[1]);
    } finally {
        foreach (glob($directory.'/*') as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
});

/** Digital PDF with vector text and a labelled color chart: the kind of figure OCR must not destroy. */
function labelledChartPdf(string $path): void
{
    (new Process([config('conversions.python'), '-I', '-c', <<<'PY'
import io, sys, pymupdf
from PIL import Image, ImageDraw, ImageFont
image = Image.new('RGB', (1400, 900), (40, 90, 160))
draw = ImageDraw.Draw(image)
font = ImageFont.load_default(size=44)
draw.rectangle((60, 500, 240, 860), fill=(240, 120, 30))
draw.rectangle((300, 300, 480, 860), fill=(70, 200, 90))
for i, text in enumerate(['Grafico de causas terminadas', 'Enero 120 casos', 'Febrero 240 casos', 'Marzo 360 casos']):
    draw.text((700, 120 + i * 90), text, fill='white', font=font)
data = io.BytesIO()
image.save(data, format='PNG')
doc = pymupdf.open()
page = doc.new_page()
page.insert_text((72, 80), 'Informe digital con texto seleccionable', fontsize=11)
page.insert_image(pymupdf.Rect(72, 120, 540, 420), stream=data.getvalue())
doc.save(sys.argv[1])
PY, $path]))->mustRun();
}

/** Images of the first page as [width, height, sha1 of its bytes]. */
function firstPageImages(string $path): array
{
    $process = new Process([config('conversions.python'), '-I', '-c', <<<'PY'
import hashlib, json, sys, pymupdf
doc = pymupdf.open(sys.argv[1])
page = doc[0]
print(json.dumps([[round(rect.width), round(rect.height), hashlib.sha1(doc.extract_image(image[0])['image']).hexdigest()]
                  for image in page.get_images(full=True) for rect in page.get_image_rects(image[0])]))
PY, $path]);
    $process->mustRun();

    return json_decode($process->getOutput(), true);
}

it('keeps a labelled chart whole in automatic mode, also through the full conversion', function () {
    enableOcr();
    $input = tempnam(sys_get_temp_dir(), 'chart');
    labelledChartPdf($input);
    $output = $input.'.out.pdf';
    $process = new Process([config('conversions.python'), '-I', base_path('scripts/rebuild_scanned_pdf.py'), $input, $output, '--lang', 'spa']);
    $process->setTimeout(120);
    $process->mustRun();

    expect($process->getOutput())->toContain('"pages_rebuilt": []')
        ->and(firstPageImages($output))->toBe(firstPageImages($input));

    $job = queuedPdfConversion(file_get_contents($input));
    (new ConvertFile($job->id))->handle();
    $job->refresh();
    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path($job->output_path));
    $media = array_filter(array_map(fn (int $i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1)), fn (string $name) => str_starts_with($name, 'word/media/'));
    $zip->close();

    expect($job->status)->toBe('ready')
        ->and($job->notice)->toBeNull()
        ->and($media)->not->toBeEmpty();
    unlink($input);
    unlink($output);
})->skip(fn () => ! ocrAvailable() || ! pdf2docxAvailable(), 'OCR no disponible');

it('never slices a chart into strips when the transcription is forced', function () {
    $input = tempnam(sys_get_temp_dir(), 'chart');
    labelledChartPdf($input);
    $output = $input.'.out.pdf';
    $process = new Process([config('conversions.python'), '-I', base_path('scripts/rebuild_scanned_pdf.py'), $input, $output, '--lang', 'spa', '--pages', 'all']);
    $process->setTimeout(120);
    $process->mustRun();

    // Strips are crops as wide as the chart: at most one may remain.
    $wide = array_filter(firstPageImages($output), fn (array $image) => $image[0] >= 400);
    expect(count($wide))->toBeLessThanOrEqual(1);
    unlink($input);
    unlink($output);
})->skip(fn () => ! ocrAvailable(), 'OCR no disponible');

it('transcribes a scanned sheet and leaves the vector footer beside it intact', function () {
    $input = tempnam(sys_get_temp_dir(), 'sheet');
    (new Process([config('conversions.python'), '-I', '-c', <<<'PY'
import io, sys, pymupdf
from PIL import Image, ImageDraw, ImageFont
image = Image.new('RGB', (1400, 1000), (250, 247, 240))
draw = ImageDraw.Draw(image)
font = ImageFont.load_default(size=44)
for i, text in enumerate(['El tribunal dicta sentencia definitiva en esta causa', 'Los intervinientes fueron oidos en la audiencia', 'Se ordena notificar a las partes de lo resuelto']):
    draw.text((60, 100 + i * 110), text, fill=(60, 40, 30), font=font)
data = io.BytesIO()
image.save(data, format='PNG')
doc = pymupdf.open()
page = doc.new_page()
page.insert_image(pymupdf.Rect(72, 100, 540, 440), stream=data.getvalue())
page.insert_text((72, 780), 'Pie vectorial verificable', fontsize=9)
doc.save(sys.argv[1])
PY, $input]))->mustRun();
    $output = $input.'.out.pdf';
    $process = new Process([config('conversions.python'), '-I', base_path('scripts/rebuild_scanned_pdf.py'), $input, $output, '--lang', 'spa']);
    $process->setTimeout(120);
    $process->mustRun();

    expect($process->getOutput())->toContain('"pages_rebuilt": [1]')
        ->and(pdfPageTexts($output)[0])->toContain('tribunal', 'sentencia', 'audiencia', 'Pievectorialverificable');
    unlink($input);
    unlink($output);
})->skip(fn () => ! ocrAvailable(), 'OCR no disponible');

/** Runs the rebuild over a one-image PDF built by $draw (Python using `image`, `draw`, `font`) and returns its result. */
function rebuiltImagePdf(string $draw, array $size, string $mode = 'auto', string $footer = 'Pie de pagina'): array
{
    $input = tempnam(sys_get_temp_dir(), 'image');
    $script = <<<PY
import io, sys, pymupdf
from PIL import Image, ImageDraw, ImageFont
image = Image.new('RGB', ({$size[0]}, {$size[1]}), (250, 247, 240))
draw = ImageDraw.Draw(image)
font = ImageFont.load_default(size=44)
text = ['El tribunal dicta sentencia definitiva en esta causa penal', 'Los intervinientes fueron oidos en la audiencia publica', 'Se ordena notificar a las partes de todo lo resuelto hoy']
{$draw}
data = io.BytesIO()
image.save(data, format='PNG')
doc = pymupdf.open()
page = doc.new_page()
page.insert_image(pymupdf.Rect(72, 100, 540, 500), stream=data.getvalue())
page.insert_text((72, 790), '{$footer}', fontsize=9)
doc.save(sys.argv[1])
PY;
    (new Process([config('conversions.python'), '-I', '-c', $script, $input]))->mustRun();
    $output = $input.'.out.pdf';
    $process = new Process([config('conversions.python'), '-I', base_path('scripts/rebuild_scanned_pdf.py'), $input, $output, '--lang', 'spa', '--pages', $mode]);
    $process->setTimeout(120);
    $process->mustRun();
    $result = ['summary' => $process->getOutput(), 'images' => firstPageImages($output), 'original' => firstPageImages($input), 'text' => pdfPageTexts($output)[0]];
    unlink($input);
    unlink($output);

    return $result;
}

it('leaves a light figure untouched in automatic mode, unlike a written sheet', function (string $draw) {
    $result = rebuiltImagePdf($draw, [1400, 900]);

    expect($result['summary'])->toContain('"pages_rebuilt": []')
        ->and($result['images'])->toBe($result['original']);
})->with([
    'bar chart on white' => <<<'PY'
draw.rectangle((0, 0, 1400, 900), fill='white')
draw.line((60, 60, 60, 840), fill='black', width=4)
draw.line((60, 840, 1340, 840), fill='black', width=4)
draw.rectangle((120, 500, 260, 838), fill=(150, 150, 150))
draw.rectangle((320, 300, 460, 838), fill=(90, 90, 90))
for i, t in enumerate(text * 2): draw.text((520, 120 + i * 90), t[:34], fill='black', font=font)
PY,
    'framed screenshot' => <<<'PY'
draw.rectangle((0, 0, 1400, 900), fill=(250, 250, 250))
draw.rectangle((20, 20, 1380, 880), outline=(120, 120, 120), width=3)
draw.line((20, 110, 1380, 110), fill=(120, 120, 120), width=3)
for i, t in enumerate(text * 3): draw.text((50, 130 + i * 72), t, fill='black', font=font)
PY,
])->skip(fn () => ! ocrAvailable(), 'OCR no disponible');

it('keeps thin ink as an image instead of losing it or reading it as text', function (string $ink) {
    $draw = "for i, t in enumerate(text * 3): draw.text((60, 60 + i * 80), t, fill=(50, 40, 30), font=font)\n".$ink;
    foreach (['auto', 'all'] as $mode) {
        $result = rebuiltImagePdf($draw, [1400, 1300], $mode);
        expect($result['summary'])->toContain('"pages_rebuilt": [1]')
            ->and(count($result['images']))->toBe(1, "$mode debe conservar la tinta como una imagen")
            ->and($result['text'])->toContain('tribunal', 'audiencia');
    }
})->with([
    'thin signature' => "draw.line([(300, 1000), (420, 930), (360, 1040), (560, 950), (640, 1010)], fill='black', width=3)",
    'horizontal rule' => "draw.line((60, 1000, 1300, 1000), fill='black', width=3)",
])->skip(fn () => ! ocrAvailable(), 'OCR no disponible');

it('does not turn a rotated scan into garbage', function () {
    $draw = "for i, t in enumerate(text * 3): draw.text((60, 60 + i * 80), t, fill=(50, 40, 30), font=font)\nimage = image.rotate(90, expand=True)";
    foreach (['auto', 'all'] as $mode) {
        $result = rebuiltImagePdf($draw, [1400, 1300], $mode);
        expect($result['summary'])->toContain('"pages_rebuilt": []')
            ->and($result['images'])->toBe($result['original']);
    }
})->skip(fn () => ! ocrAvailable(), 'OCR no disponible');

it('does not start Tesseract for a PDF without images', function () {
    $input = tempnam(sys_get_temp_dir(), 'pdf-text');
    $output = $input.'.pdf';
    try {
        file_put_contents($input, numberedPdf('Texto digital', 1));
        $process = new Process([config('conversions.python'), '-I', base_path('scripts/rebuild_scanned_pdf.py'), $input, $output, '--tesseract', '/missing/tesseract']);
        $process->mustRun();
        expect($process->getOutput())->toContain('"pages_rebuilt": []');
    } finally {
        @unlink($input);
        @unlink($output);
    }
});

it('reports an exhausted conversion deadline and keeps the queue budget below retry after', function () {
    enableOcr();
    config()->set('conversions.ocr_timeout', 1800);
    $tool = new PdfToWordTool('pdf', []);
    expect($tool->timeout([]))->toBeLessThan(2100)
        ->and(1800 + 30)->toBeLessThanOrEqual($tool->timeout([]));
    $remaining = new ReflectionMethod($tool, 'remaining');
    expect(fn () => $remaining->invoke($tool, microtime(true) - 1))
        ->toThrow(InvalidArgumentException::class, 'El documento es demasiado largo para convertirlo. Divídelo e inténtalo de nuevo.');
});
