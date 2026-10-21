<?php

use App\Jobs\ConvertFile;
use App\Livewire\BackofficeFileConverterPage;
use App\Livewire\FileConversionHistoryPage;
use App\Livewire\PublicFileConverterPage;
use App\Models\FileConversion;
use App\Models\User;
use App\Services\FileConversionService;
use App\Services\LibreOfficeDocxNormalizer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Symfony\Component\Process\Process;

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

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

it('migrates conversion permissions when AdminOperacion is absent and can be retried', function () {
    Role::where('name', 'AdminOperacion')->where('guard_name', 'web')->delete();
    $role = Role::findOrCreate('SuperAdmin', 'web');
    $migration = require database_path('migrations/2026_09_23_000001_add_file_conversion_permissions.php');

    $migration->up();
    $migration->up();

    expect($role->fresh()->hasPermissionTo('navbar.conversiones.visible'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('conversiones.ver_registro'))->toBeTrue()
        ->and(Role::where('name', 'AdminOperacion')->exists())->toBeFalse();
});

it('limits frontoffice to two conversions per format and records the declared actor', function () {
    $pdf = fn () => UploadedFile::fake()->create('acta.pdf', 10, 'application/pdf');
    $audio = fn () => UploadedFile::fake()->create('sala.m4a', 10, 'audio/mp4');

    for ($i = 0; $i < 2; $i++) {
        Livewire::test(PublicFileConverterPage::class)
            ->set('actorName', 'María Pérez')
            ->set('file', $pdf())
            ->call('convert')
            ->assertHasNoErrors();
    }

    Livewire::test(PublicFileConverterPage::class)
        ->set('actorName', 'María Pérez')
        ->set('file', $pdf())
        ->call('convert')
        ->assertHasErrors('file');

    Livewire::test(PublicFileConverterPage::class)
        ->call('selectFormat', 'm4a')
        ->set('actorName', 'María Pérez')
        ->set('file', $audio())
        ->call('convert')
        ->assertHasNoErrors();

    expect(FileConversion::count())->toBe(3)
        ->and(FileConversion::first()->actor_name)->toBe('María Pérez')
        ->and(FileConversion::first()->user_id)->toBeNull()
        ->and(FileConversion::first()->ip_address)->not->toBeEmpty();
    Queue::assertPushed(ConvertFile::class, 3);
});

it('requires a name in frontoffice and protects downloads by session', function () {
    Livewire::test(PublicFileConverterPage::class)
        ->set('file', UploadedFile::fake()->create('acta.pdf', 10, 'application/pdf'))
        ->call('convert')
        ->assertHasErrors('actorName');

    $job = FileConversion::create([
        'token' => (string) Str::uuid(), 'area' => 'frontoffice', 'format' => 'pdf',
        'status' => 'ready', 'original_name' => 'acta.pdf', 'result_name' => 'acta.docx',
        'input_path' => 'conversions/test/original.pdf', 'output_path' => 'conversions/test/result.docx',
        'actor_name' => 'Ana', 'ip_address' => '127.0.0.1', 'session_hash' => hash('sha256', 'another-session'),
        'expires_at' => now()->addHour(),
    ]);
    Storage::disk('local')->put($job->output_path, 'docx');

    $this->get(route('conversions.download', $job))->assertForbidden();
});

it('shows the public page and allows the owning session to download a completed file', function () {
    $this->get(route('conversions.public'))->assertSuccessful()->assertSee('Nombre de quien realiza la conversión');

    Livewire::test(PublicFileConverterPage::class)
        ->set('actorName', 'Ana')
        ->set('file', UploadedFile::fake()->create('acta.pdf', 10, 'application/pdf'))
        ->call('convert')->assertHasNoErrors();

    $job = FileConversion::firstOrFail();
    $job->update(['status' => 'ready', 'output_path' => "conversions/{$job->token}/result.docx", 'expires_at' => now()->addHour()]);
    Storage::disk('local')->put($job->output_path, 'docx');

    expect($job->session_hash)->toBe(hash('sha256', session()->getId()));
    $this->withCookie(config('session.cookie'), session()->getId());

    $this->get(route('conversions.download', $job))->assertSuccessful()->assertDownload('acta.docx');
    $job->update(['expires_at' => now()->subMinute()]);
    $this->get(route('conversions.download', $job))->assertNotFound();
});

it('marks unavailable conversion engines as failed without consuming frontoffice quota', function () {
    config()->set('conversions.ffmpeg', '/does-not-exist/ffmpeg');
    $token = (string) Str::uuid();
    $job = FileConversion::create([
        'token' => $token, 'area' => 'frontoffice', 'format' => 'm4a',
        'status' => 'queued', 'original_name' => 'audio.m4a', 'result_name' => 'audio.mp3',
        'input_path' => "conversions/{$token}/original.m4a", 'actor_name' => 'Ana',
        'ip_address' => '127.0.0.1', 'session_hash' => hash('sha256', session()->getId()),
    ]);
    Storage::disk('local')->put($job->input_path, 'audio');

    app(ConvertFile::class, ['conversionId' => $job->id])->handle();

    expect($job->fresh()->status)->toBe('failed')
        ->and(app(FileConversionService::class)->used($job->session_hash, 'm4a'))->toBe(0);
});

it('converts a text PDF with LibreOffice and anchors its boxes to the page', function () {
    config()->set('conversions.pdf_engine', 'libreoffice');
    $pdf = conversionPdf('Acta del tribunal', styled: true);
    $job = queuedPdfConversion($pdf);

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    expect($job->status)->toBe('ready')
        ->and($job->engine)->toBe('libreoffice')
        ->and($job->input_sha256)->toBe(hash('sha256', $pdf))
        ->and($job->output_sha256)->toBe(hash_file('sha256', Storage::disk('local')->path($job->output_path)))
        ->and(docxPart($job, 'word/document.xml'))->toContain('Acta del tribunal')
        ->toContain('Texto editable')
        ->toContain('<w:b')
        ->toContain('relativeFrom="page"')
        ->not->toContain('relativeFrom="column"')
        ->not->toContain('relativeFrom="paragraph"');
    Storage::disk('local')->assertMissing("conversions/{$job->token}/converted.docx");
});

it('reconstructs paragraphs with pdf2docx and moves repeated page content to the header and footer', function () {
    config()->set('conversions.pdf_engine', 'pdf2docx');
    $footer = 'BT /F1 8 Tf 400 40 Td (Codigo: KPXXCVKWLRX) Tj ET';
    $job = queuedPdfConversion(conversionPdfPages([
        'BT /F2 16 Tf 72 700 Td (Acta del tribunal) Tj ET BT /F1 12 Tf 72 670 Td (Primera pagina del documento judicial.) Tj ET '.$footer,
        'BT /F1 12 Tf 72 700 Td (Segunda pagina con mas texto editable.) Tj ET '.$footer,
    ]));

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    $body = docxPart($job, 'word/document.xml');
    expect($job->status)->toBe('ready')
        ->and($job->engine)->toBe('pdf2docx')
        ->and($body)->toContain('Acta del tribunal')->toContain('Segunda pagina')->toContain('<w:b')
        ->not->toContain('w:txbxContent')
        ->not->toContain('KPXXCVKWLRX')
        ->and(docxPart($job, 'word/footer1.xml'))->toContain('Codigo: KPXXCVKWLRX')->toContain('mso-position-vertical-relative:page');
})->skip(fn () => ! pdf2docxAvailable(), 'pdf2docx is not installed for CONVERSIONS_PYTHON');

it('keeps the page count when the letterhead is missing from the last page', function () {
    config()->set('conversions.pdf_engine', 'pdf2docx');
    $letterhead = 'BT /F2 12 Tf 72 760 Td (Poder Judicial) Tj ET BT /F1 9 Tf 72 30 Td (Pedro Montt 1606 Torre G 4) Tj ET ';
    $body = fn (string $text) => 'BT /F1 12 Tf 72 700 Td ('.$text.') Tj ET';
    $job = queuedPdfConversion(conversionPdfPages([
        $letterhead.$body('Primera pagina del auto de apertura.'),
        $letterhead.$body('Segunda pagina del auto de apertura.'),
        $body('Pagina final con la firma del juez.'),
    ]));

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    $body = docxPart($job, 'word/document.xml');
    $footers = array_filter(array_map(fn ($n) => docxPart($job, "word/footer{$n}.xml"), range(1, 4)));
    expect($job->status)->toBe('ready')
        ->and(substr_count($body, '<w:sectPr'))->toBe(3)
        ->and($body)->not->toContain('Pedro Montt')->not->toContain('Poder Judicial')
        ->toContain('w:lineRule="exact"')
        ->and(array_filter($footers, fn ($xml) => str_contains($xml, 'Pedro Montt')))->toHaveCount(1)
        ->and(array_filter($footers, fn ($xml) => ! str_contains($xml, 'Pedro Montt')))->toHaveCount(1);
})->skip(fn () => ! pdf2docxAvailable(), 'pdf2docx is not installed for CONVERSIONS_PYTHON');

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

it('moves each footer of a merged PDF to its own pages', function () {
    config()->set('conversions.pdf_engine', 'pdf2docx');
    $page = fn (string $footer, int $n) => 'BT /F1 12 Tf 72 700 Td (Pagina '.$n.' del expediente unido.) Tj ET BT /F1 8 Tf 400 30 Td ('.$footer.') Tj ET';
    // Three pages of a first document and four of a second: the first footer is on less than half the pages.
    $job = queuedPdfConversion(conversionPdfPages([
        $page('Codigo: PRIMERDOC', 1), $page('Codigo: PRIMERDOC', 2), $page('Codigo: PRIMERDOC', 3),
        $page('Codigo: SEGUNDODOC', 4), $page('Codigo: SEGUNDODOC', 5), $page('Codigo: SEGUNDODOC', 6), $page('Codigo: SEGUNDODOC', 7),
    ]));

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    $parts = implode('', docxHeaderFooterParts($job));
    expect($job->status)->toBe('ready')
        ->and(docxPart($job, 'word/document.xml'))->not->toContain('PRIMERDOC')->not->toContain('SEGUNDODOC')
        ->and($parts)->toContain('Codigo: PRIMERDOC')->toContain('Codigo: SEGUNDODOC');
})->skip(fn () => ! pdf2docxAvailable(), 'pdf2docx is not installed for CONVERSIONS_PYTHON');

it('turns printed page numbers into a Word page field that starts at the PDF number', function () {
    config()->set('conversions.pdf_engine', 'pdf2docx');
    $page = fn (int $printed) => 'BT /F1 12 Tf 72 700 Td (Considerando numero '.$printed.' de la sentencia.) Tj ET BT /F1 10 Tf 300 40 Td ('.$printed.') Tj ET';
    $job = queuedPdfConversion(conversionPdfPages([$page(41), $page(42), $page(43)]));

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    $body = docxPart($job, 'word/document.xml');
    expect($job->status)->toBe('ready')
        ->and(preg_match('#<w:t[^>]*>\s*4[123]\s*</w:t>#', $body))->toBe(0)
        ->and($body)->toContain('w:start="41"')
        ->and(implode('', docxHeaderFooterParts($job)))->toContain('w:instr=" PAGE "');
})->skip(fn () => ! pdf2docxAvailable(), 'pdf2docx is not installed for CONVERSIONS_PYTHON');

it('gives every page size its own bottom margin', function () {
    config()->set('conversions.pdf_engine', 'pdf2docx');
    $footer = 'BT /F1 8 Tf 400 30 Td (Codigo: TJTRBUDSNXZ) Tj ET';
    $body = fn (int $y, string $text) => 'BT /F1 12 Tf 72 '.$y.' Td ('.$text.') Tj ET ';
    // Letter pages with a footer, then a Legal page whose text reaches its bottom.
    $job = queuedPdfConversion(conversionPdfPages([
        $body(700, 'Primera pagina carta.').$footer, $body(700, 'Segunda pagina carta.').$footer, $body(700, 'Tercera pagina carta.').$footer,
        $body(950, 'Pagina oficio.').$body(60, 'Ultima linea de la pagina oficio.'),
    ], [792, 792, 792, 1008]));

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    preg_match_all('#<w:pgSz w:w="\d+" w:h="(\d+)"[^>]*/>\s*<w:pgMar [^>]*w:bottom="(\d+)"#', docxPart($job, 'word/document.xml'), $sections, PREG_SET_ORDER);
    $legal = collect($sections)->first(fn ($section) => (int) $section[1] > 18000);
    expect($job->status)->toBe('ready')
        ->and($legal)->not->toBeNull()
        ->and((int) $legal[2])->toBeLessThan(60 * 20);
})->skip(fn () => ! pdf2docxAvailable(), 'pdf2docx is not installed for CONVERSIONS_PYTHON');

it('keeps a flattened e-signature box as a picture with its text as alt text', function () {
    config()->set('conversions.pdf_engine', 'pdf2docx');
    // Two signature boxes side by side, as the tribunal stamps them: frame, blue bar, seal image and text.
    $signature = fn (int $x, string $name, string $role) => '0.18 0.43 0.70 RG 1 w '.$x.' 600 255 100 re S 0.18 0.43 0.70 rg '.$x.' 600 17 100 re f '
        .'q 80 0 0 80 '.($x + 160).' 610 cm /Im1 Do Q BT /F2 10 Tf '.($x + 22).' 670 Td ('.$name.') Tj ET '
        .'BT /F1 9 Tf '.($x + 22).' 655 Td ('.$role.') Tj ET ';
    $job = queuedPdfConversion(conversionPdfPages(['BT /F1 12 Tf 72 740 Td (Firmaron la sentencia los jueces.) Tj ET '
        .$signature(42, 'Erick Humberto Aravena Ibarra', 'Juez Presidente').$signature(300, 'Claudia Patricia Morgado Moscoso', 'Juez Integrante')]));

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    $body = docxPart($job, 'word/document.xml');
    expect($job->status)->toBe('ready')
        ->and($body)->not->toContain('<w:tbl>')
        ->toContain('descr="Firma electrónica: Erick Humberto Aravena Ibarra Juez Presidente / Claudia Patricia Morgado Moscoso Juez Integrante"')
        ->toContain('Firmaron la sentencia')
        ->and(substr_count($body, 'descr="Firma electrónica'))->toBe(1);
})->skip(fn () => ! pdf2docxAvailable(), 'pdf2docx is not installed for CONVERSIONS_PYTHON');

it('fails visibly instead of falling back when the configured engine is unavailable', function () {
    config()->set(['conversions.pdf_engine' => 'pdf2docx', 'conversions.python' => '/does-not-exist/python3']);
    $job = queuedPdfConversion(conversionPdf('Acta del tribunal'));

    (new ConvertFile($job->id))->handle();

    expect($job->fresh()->status)->toBe('failed')
        ->and($job->fresh()->engine)->toBe('pdf2docx')
        ->and($job->fresh()->output_path)->toBeNull();
});

it('rejects PDFs without selectable text with a useful message', function () {
    $job = queuedPdfConversion(conversionPdf(''), 'escaneado');

    (new ConvertFile($job->id))->handle();

    expect($job->fresh()->status)->toBe('failed')
        ->and($job->fresh()->error_message)->toContain('requieren OCR');
});

it('warns which pages of a mixed PDF have no selectable text', function () {
    config()->set('conversions.pdf_engine', 'libreoffice');
    $job = queuedPdfConversion(conversionPdfPages([
        'BT /F1 12 Tf 72 720 Td (Resolucion con texto seleccionable) Tj ET',
        'q 500 0 0 700 50 50 cm /Im1 Do Q',
    ]));

    (new ConvertFile($job->id))->handle();

    expect($job->fresh()->status)->toBe('ready')
        ->and($job->fresh()->notice)->toContain('la página 2');
});

it('recognises scanned pages with OCR when it is configured', function () {
    config()->set('conversions.pdf_engine', 'libreoffice');
    $recognised = tempnam(sys_get_temp_dir(), 'ocr-result-');
    file_put_contents($recognised, conversionPdf('Texto reconocido por OCR'));
    config()->set('conversions.ocrmypdf', fakeTool('for last; do :; done; cp '.escapeshellarg($recognised).' "$last"'));
    $job = queuedPdfConversion(conversionPdf(''), 'escaneado');

    (new ConvertFile($job->id))->handle();

    expect($job->fresh()->status)->toBe('ready')
        ->and($job->fresh()->notice)->toContain('OCR')
        ->and(docxPart($job->fresh(), 'word/document.xml'))->toContain('Texto reconocido por OCR');
    Storage::disk('local')->assertMissing("conversions/{$job->token}/ocr.pdf");
});

it('explains password protected and damaged PDFs', function (string $stderr, string $message) {
    config()->set('conversions.pdfinfo', fakeTool('echo '.escapeshellarg($stderr).' >&2; exit 1'));
    $job = queuedPdfConversion(conversionPdf('Acta del tribunal'));

    (new ConvertFile($job->id))->handle();

    expect($job->fresh()->status)->toBe('failed')
        ->and($job->fresh()->error_message)->toContain($message);
})->with([
    'password' => ['Command Line Error: Incorrect password', 'protegido con contraseña'],
    'damaged' => ['Syntax Error: Couldn\'t find trailer dictionary', 'dañado'],
]);

it('shows the upload limit the server really accepts', function () {
    config()->set('conversions.pdf_max_kb', 1024 * 1024);
    $phpLimit = Symfony\Component\HttpFoundation\File\UploadedFile::getMaxFilesize();

    Livewire::test(PublicFileConverterPage::class)
        ->assertSee('máximo '.Number::fileSize($phpLimit, maxPrecision: 1))
        ->assertSee('> '.$phpLimit, false);
});

it('rejects PDFs over the page limit', function () {
    config()->set('conversions.pdf_max_pages', 1);
    $job = queuedPdfConversion(conversionPdfPages(['BT /F1 12 Tf 72 720 Td (Uno) Tj ET', 'BT /F1 12 Tf 72 720 Td (Dos) Tj ET']));

    (new ConvertFile($job->id))->handle();

    expect($job->fresh()->error_message)->toContain('máximo es 1');
});

it('sends white LibreOffice backgrounds behind the text but keeps coloured masks', function () {
    $path = tempnam(sys_get_temp_dir(), 'normalize-').'.docx';
    $archive = new ZipArchive;
    $archive->open($path, ZipArchive::CREATE);
    $archive->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:v="urn:schemas-microsoft-com:vml"><w:body>'
        .'<wp:anchor><wp:positionH relativeFrom="column"/><wp:positionV relativeFrom="paragraph"/></wp:anchor>'
        .'<v:shape id="white" coordsize="10,10" fillcolor="white" style="position:absolute;margin-left:1pt"/>'
        .'<v:shape id="black" coordsize="10,10" fillcolor="black" style="position:absolute;margin-left:2pt"/>'
        .'</w:body></w:document>');
    $archive->addFromString('word/media/image1.png', 'unchanged');
    $archive->close();

    app(LibreOfficeDocxNormalizer::class)->normalize($path);

    $archive->open($path);
    $xml = $archive->getFromName('word/document.xml');
    expect($xml)->toContain('relativeFrom="page"')->not->toContain('relativeFrom="column"')
        ->and($xml)->toMatch('/id="white"[^>]*z-index:-2147483000/')
        ->and($xml)->not->toMatch('/id="black"[^>]*z-index/')
        ->and($xml)->toMatch('/id="black"[^>]*mso-position-vertical-relative:page/')
        ->and($archive->getFromName('word/media/image1.png'))->toBe('unchanged');
    $archive->close();
});

it('allows authorized backoffice users unlimited jobs and limits access to the register', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('navbar.conversiones.visible');

    for ($i = 0; $i < 3; $i++) {
        Livewire::actingAs($user)->test(BackofficeFileConverterPage::class)
            ->set('file', UploadedFile::fake()->create('acta.pdf', 10, 'application/pdf'))
            ->call('convert')->assertHasNoErrors();
    }

    expect(FileConversion::where('user_id', $user->id)->count())->toBe(3);
    $this->actingAs($user)->get(route('conversions.history'))->assertForbidden();
    $user->givePermissionTo('conversiones.ver_registro');
    Livewire::actingAs($user)->test(FileConversionHistoryPage::class)->assertSee('acta.pdf');
});

it('cleans expired files but preserves the conversion audit', function () {
    $token = (string) Str::uuid();
    $job = FileConversion::create([
        'token' => $token, 'area' => 'frontoffice', 'format' => 'pdf',
        'status' => 'ready', 'original_name' => 'acta.pdf', 'result_name' => 'acta.docx',
        'input_path' => "conversions/{$token}/original.pdf", 'output_path' => "conversions/{$token}/result.docx",
        'actor_name' => 'Ana', 'ip_address' => '127.0.0.1', 'session_hash' => hash('sha256', 'test'),
        'expires_at' => now()->subMinute(),
    ]);
    Storage::disk('local')->put($job->output_path, 'docx');

    $this->artisan('conversions:clean')->assertSuccessful();
    Storage::disk('local')->assertMissing($job->output_path);
    $this->assertModelExists($job);
    expect($job->fresh()->files_deleted_at)->not->toBeNull();
});
