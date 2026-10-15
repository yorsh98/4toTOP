<?php

use App\Jobs\ConvertFile;
use App\Livewire\BackofficeFileConverterPage;
use App\Livewire\FileConversionHistoryPage;
use App\Livewire\PublicFileConverterPage;
use App\Models\FileConversion;
use App\Models\User;
use App\Services\FileConversionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

function conversionPdf(string $content, bool $styled = false): string
{
    $stream = $content === '' ? '' : ($styled
        ? 'BT /F2 18 Tf 72 750 Td ('.$content.') Tj ET BT /F1 12 Tf 72 720 Td (Texto editable) Tj ET'
        : 'BT /F1 12 Tf 72 720 Td ('.$content.') Tj ET');
    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R'.($styled ? ' /F2 6 0 R' : '').' >> >> /Contents 5 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        '<< /Length '.strlen($stream).' >>'."\nstream\n{$stream}\nendstream",
    ];
    if ($styled) {
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
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

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
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

it('converts a text PDF to an editable DOCX with LibreOffice', function () {
    $token = (string) Str::uuid();
    $job = FileConversion::create([
        'token' => $token, 'area' => 'frontoffice', 'format' => 'pdf',
        'status' => 'queued', 'original_name' => 'acta.pdf', 'result_name' => 'acta.docx',
        'input_path' => "conversions/{$token}/original.pdf", 'actor_name' => 'Ana',
        'ip_address' => '127.0.0.1', 'session_hash' => hash('sha256', 'test'),
    ]);
    Storage::disk('local')->put($job->input_path, conversionPdf('Acta del tribunal', styled: true));

    (new ConvertFile($job->id))->handle();

    $job->refresh();
    expect($job->status)->toBe('ready');
    $document = new ZipArchive;
    expect($document->open(Storage::disk('local')->path($job->output_path)))->toBeTrue()
        ->and($document->getFromName('word/document.xml'))->toContain('Acta del tribunal')
        ->toContain('Texto editable')
        ->toContain('<w:b');
    $document->close();
    Storage::disk('local')->assertMissing("conversions/{$token}/extracted.txt");
});

it('rejects PDFs without selectable text with a useful message', function () {
    $token = (string) Str::uuid();
    $job = FileConversion::create([
        'token' => $token, 'area' => 'frontoffice', 'format' => 'pdf',
        'status' => 'queued', 'original_name' => 'escaneado.pdf', 'result_name' => 'escaneado.docx',
        'input_path' => "conversions/{$token}/original.pdf", 'actor_name' => 'Ana',
        'ip_address' => '127.0.0.1', 'session_hash' => hash('sha256', 'test'),
    ]);
    Storage::disk('local')->put($job->input_path, conversionPdf(''));

    (new ConvertFile($job->id))->handle();

    expect($job->fresh()->status)->toBe('failed')
        ->and($job->fresh()->error_message)->toContain('requieren OCR');
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
