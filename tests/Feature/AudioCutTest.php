<?php

use App\Conversions\AudioCutTool;
use App\Conversions\ConversionTool;
use App\Jobs\ConvertFile;
use App\Livewire\PublicFileConverterPage;
use App\Models\FileConversion;
use App\Services\AudioInspector;
use App\Services\FileConversionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

it('sorts merges clamps and complements regions', function ($regions, $mode, $expected) {
    expect(AudioCutTool::spans($regions, $mode, 10.0))->toEqual($expected);
})->with([
    [[[5, 7], [1, 3], [2.5, 4]], 'keep', [[1, 4], [5, 7]]],
    [[[1, 2], [2, 3]], 'keep', [[1, 3]]],
    [[[0, 2], [8, 10]], 'remove', [[2, 8]]],
    [[[3, 4]], 'remove', [[0, 3], [4, 10]]],
    [[[-0.03, 10.04]], 'keep', [[0, 10]]],
    [[[2, 4], [1, 3]], 'silence', [[1, 4]]],
]);

it('rejects invalid or empty fragments', function ($regions, $mode, $message) {
    expect(fn () => AudioCutTool::spans($regions, $mode, 10.0))->toThrow(InvalidArgumentException::class, $message);
})->with([
    [[], 'keep', 'Marca al menos'],
    [[[2, 1]], 'keep', 'demasiado corto'],
    [[[1, 1.049]], 'keep', 'demasiado corto'],
    [[[1, 1.05]], 'keep', 'quedaría vacío'],
    [[[0, 10]], 'remove', 'quedaría vacío'],
    [[[9, 10.2]], 'keep', 'fuera del audio'],
    [[[-0.051, 10]], 'keep', 'fuera del audio'],
    [[['1', 2]], 'keep', 'no son válidos'],
    [[[false, 2]], 'keep', 'no son válidos'],
    [[[NAN, 2]], 'keep', 'no son válidos'],
    [[[1, INF]], 'keep', 'no son válidos'],
    [[['start' => 1, 'end' => 2]], 'keep', 'no son válidos'],
    [[1], 'keep', 'no son válidos'],
    [array_fill(0, 201, [1, 2]), 'keep', 'hasta 200 fragmentos'],
]);

it('formats time with milliseconds and carries rounding', function ($seconds, $expected) {
    expect(AudioInspector::clock($seconds))->toBe($expected);
})->with([[0, '0:00.000'], [65.25, '1:05.250'], [3725.5, '1:02:05.500'], [59.9999, '1:00.000']]);

it('states the duration limit in words', function ($seconds, $expected) {
    expect(AudioInspector::limit($seconds))->toBe($expected);
})->with([[14400, '4 horas'], [3600, '1 hora'], [90, '1:30.000']]);

it('opens the audio cutter dialog with its four steps from the URL', function () {
    expect(config('conversions.tools.audiocut.group'))->toBe('Editar');
    $page = Livewire::withQueryParams(['herramienta' => 'audiocut'])->test(PublicFileConverterPage::class)
        ->assertSet('format', 'audiocut')
        ->assertSeeHtml('id="audio-cutter"')
        ->assertSee(['Sube el audio', 'Marca los fragmentos', 'Elige qué hacer', 'Corta y descarga', 'Abrir el cortador',
            'Quedarme solo con lo marcado', 'Quitar lo marcado', 'Silenciar lo marcado', 'FLAC, AAC, WMA o AMR', 'y 4 horas'])
        ->assertDontSee(['OGA', 'ASF']);

    // The file input lives only in the dialog: the general form does not render a second one.
    expect(substr_count($page->html(), 'id="conversion-file"'))->toBe(1);
});

it('does not render the audio cutter for other tools', function () {
    Livewire::test(PublicFileConverterPage::class)->call('selectFormat', 'pdf')
        ->assertDontSeeHtml('id="audio-cutter"')
        ->assertSeeHtml('id="conversion-file"');
});

it('names each result type on its download button', function ($extension, $label) {
    expect(ConversionTool::downloadLabel($extension))->toBe($label);
})->with([['wav', 'WAV'], ['m4a', 'M4A'], ['ogg', 'OGG'], ['FLAC', 'FLAC'], ['mp3', 'MP3'], ['zip', 'ZIP'], ['docx', 'Word'], ['xyz', 'archivo']]);

function audioCutPage(string $extension = 'wav', float $duration = 10): Testable
{
    $content = conversionAudio($extension, $duration);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);

    return Livewire::test(PublicFileConverterPage::class)->call('selectFormat', 'audiocut')
        ->set('actorName', 'Prueba audio')
        ->set('file', UploadedFile::fake()->createWithContent('tono.'.$extension, $content)->mimeType($mime));
}

function runAudioCut(array $options = [], string $extension = 'wav'): FileConversion
{
    $page = audioCutPage($extension);
    foreach (['segments' => '[[1,3],[6,7.5]]', ...$options] as $key => $value) {
        $page->set('options.'.$key, $value);
    }
    // The cutter keeps the file (to cut it again) and follows the new job in its last step.
    $page->call('convert')->assertHasNoErrors()
        ->assertSet('lastToken', fn ($token) => $token !== null)->assertSet('audioDuration', fn ($duration) => $duration > 0);
    $job = FileConversion::latest('id')->firstOrFail();
    (new ConvertFile($job->id))->handle();
    $job->refresh();
    expect($job->status)->toBe('ready', $job->error_message ?? '');

    return $job;
}

it('probes a tone and sends compact peaks only in the upload event', function () {
    $page = audioCutPage()->assertHasNoErrors()
        ->assertSet('audioDuration', fn ($duration) => abs($duration - 10) < 0.05)
        ->assertDispatched('audio-loaded', function ($event, $params) {
            $bytes = base64_decode($params['peaks']);

            return $params['perSecond'] === 50 && abs(strlen($bytes) - 500) <= 2 && max(unpack('C*', $bytes)) > 0;
        });
    $page->call('selectFormat', 'pdf')->assertSet('audioDuration', null);
})->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('rejects disguised and overlong audio without consuming quota', function () {
    $page = Livewire::test(PublicFileConverterPage::class)->call('selectFormat', 'audiocut')->set('actorName', 'Ana')
        ->set('file', UploadedFile::fake()->createWithContent('fake.mp3', conversionPdf('No es audio')))
        ->assertHasErrors(['file']);
    config()->set('conversions.audio_max_seconds', 5);
    $page->set('file', UploadedFile::fake()->createWithContent('largo.wav', conversionAudio('wav')))->assertHasErrors(['file']);
    expect(FileConversion::count())->toBe(0)
        ->and(app(FileConversionService::class)->used(hash('sha256', session()->getId()), 'audiocut'))->toBe(0);
    Queue::assertNothingPushed();
})->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('keeps or removes marked spans and verifies the delivered duration', function ($mode, $segments, $duration, $notice) {
    $job = runAudioCut(['mode' => $mode, 'segments' => $segments]);
    expect(abs(audioDuration(Storage::disk('local')->path($job->output_path)) - $duration))->toBeLessThanOrEqual(0.15)
        ->and($job->result_name)->toBe('tono_recortado.mp3')->and($job->notice)->toContain($notice);
})->with([
    ['keep', '[[1,3],[6,7.5]]', 3.5, 'Se conservaron 2 fragmentos'],
    ['remove', '[[2,4]]', 8, 'Se quitaron'],
])->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('mutes the selected span without changing duration or the other audio', function () {
    $job = runAudioCut(['mode' => 'silence', 'segments' => '[[2,4]]', 'separate' => true]);
    $path = Storage::disk('local')->path($job->output_path);
    expect(abs(audioDuration($path) - 10))->toBeLessThanOrEqual(0.15)
        ->and(meanVolume($path, 2.2, 3.8))->toBeLessThan(-60)
        ->and(meanVolume($path, 5, 9))->toBeGreaterThan(-30)
        ->and($job->options['separate'])->toBeFalse();
})->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('exports separate regions in a ZIP and a single region without one', function () {
    $job = runAudioCut(['segments' => '[[0,1],[2,4],[6,9]]', 'separate' => true, 'normalize' => true]);
    expect($job->result_name)->toBe('tono_recortado.zip');
    $archive = new ZipArchive;
    $archive->open(Storage::disk('local')->path($job->output_path));
    expect($archive->numFiles)->toBe(3);
    foreach ([1, 2, 3] as $index => $duration) {
        expect($archive->getNameIndex($index))->toBe(sprintf('tono_%02d.mp3', $index + 1));
        $path = tempnam(sys_get_temp_dir(), 'audio-part-');
        try {
            file_put_contents($path, $archive->getFromIndex($index));
            expect(abs(audioDuration($path) - $duration))->toBeLessThanOrEqual(0.15);
        } finally {
            unlink($path);
        }
    }
    $archive->close();
    $single = runAudioCut(['segments' => '[[1,3]]', 'separate' => true, 'fade_ms' => 500]);
    expect($single->result_name)->toBe('tono_recortado.mp3')
        ->and(abs(audioDuration(Storage::disk('local')->path($single->output_path)) - 2))->toBeLessThanOrEqual(0.15);
})->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('crossfades joins and rejects fades longer than the spans', function () {
    $job = runAudioCut(['segments' => '[[0,3],[5,8]]', 'fade_ms' => 500]);
    expect(abs(audioDuration(Storage::disk('local')->path($job->output_path)) - 5.5))->toBeLessThanOrEqual(0.15);
    audioCutPage()->set('options.segments', '[[0,0.6],[5,8]]')->set('options.fade_ms', 500)
        ->call('convert')->assertHasErrors(['options.fade_ms']);
})->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('normalizes audio while preserving its sample rate channels and duration', function () {
    $job = runAudioCut(['normalize' => true, 'output' => 'wav']);
    $path = Storage::disk('local')->path($job->output_path);
    $info = app(AudioInspector::class)->probe($path);
    expect(abs($info['duration'] - 3.5))->toBeLessThanOrEqual(0.15)
        ->and($info['sample_rate'])->toBe(44100)->and($info['channels'])->toBe(1)
        ->and(meanVolume($path, 0.2, 3))->toBeGreaterThan(-20);
})->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('encodes each output format', function ($extension) {
    if (! audioEncoderAvailable($extension)) {
        $this->markTestSkipped('Missing encoder: '.audioEncoder($extension));
    }
    $job = runAudioCut(['output' => $extension]);
    expect($job->result_name)->toBe('tono_recortado.'.$extension)
        ->and(abs(audioDuration(Storage::disk('local')->path($job->output_path)) - 3.5))->toBeLessThanOrEqual(0.15);
})->with(['mp3', 'm4a', 'wav', 'ogg', 'flac'])->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('validates probes and cuts each accepted input format', function ($extension) {
    if (! in_array($extension, config('conversions.tools.audiocut.accept'))) {
        $this->markTestSkipped('Not accepted by MIME validation: '.$extension);
    }
    if (! audioEncoderAvailable($extension)) {
        $this->markTestSkipped('Missing encoder: '.audioEncoder($extension));
    }
    $job = runAudioCut([], $extension);
    expect(abs(audioDuration(Storage::disk('local')->path($job->output_path)) - 3.5))->toBeLessThanOrEqual(0.15);
})->with(['mp3', 'm4a', 'wav', 'ogg', 'oga', 'opus', 'flac', 'aac', 'wma', 'asf', 'amr'])->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('rejects manipulated segments duration and excess public usage', function () {
    $page = audioCutPage()->set('audioDuration', 999)->set('options.segments', '[[0,999]]')
        ->call('convert')->assertHasErrors(['options.segments']);
    foreach (['{}', '{"start":0}', 'null', '[[0,1,2]]'] as $segments) {
        $page->set('options.segments', $segments)->call('convert')->assertHasErrors(['options.segments']);
    }
    expect(FileConversion::count())->toBe(0);
    $page->set('options.segments', '[[1,3]]')->call('convert')->assertHasNoErrors();
    expect(FileConversion::sole()->options['duration'])->toBeLessThan(10.05);
    Queue::assertPushed(ConvertFile::class, fn ($job) => $job->timeout === 360);
    config()->set('conversions.public_limit', 1);
    audioCutPage()->set('options.segments', '[[1,3]]')->call('convert')->assertHasErrors(['file']);
})->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('budgets time for four hours of audio', function () {
    expect(ConversionTool::for('audiocut')->timeout(['duration' => 14400]))->toBe(1560);
});

it('follows the job in the last step and can start over or mark the same audio again', function () {
    $page = audioCutPage()->set('options.segments', '[[1,3]]')->set('options.output', 'wav')
        ->call('convert')->assertHasNoErrors()->assertSee('En espera');
    $job = FileConversion::latest('id')->firstOrFail();
    expect($page->get('lastToken'))->toBe($job->token);

    (new ConvertFile($job->id))->handle();
    $page->call('$refresh')->assertSee(['Listo', 'Descargar WAV', 'Volver a marcar este audio', 'Cortar otro audio']);

    $page->call('editAgain')->assertSet('lastToken', null)->assertSet('options.segments', '[[1,3]]')
        ->assertSet('audioDuration', fn ($duration) => $duration > 0)->assertDontSee('Volver a marcar este audio');

    $page->call('startOver')->assertSet('lastToken', null)->assertSet('file', null)->assertSet('audioDuration', null)
        ->assertSet('options.segments', '[]');
})->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');

it('keeps another user\'s job out of the last step', function () {
    $page = Livewire::test(PublicFileConverterPage::class)->call('selectFormat', 'audiocut');
    $foreign = FileConversion::create([
        'token' => (string) Str::uuid(), 'area' => 'frontoffice', 'format' => 'audiocut', 'status' => 'ready',
        'original_name' => 'ajeno.mp3', 'result_name' => 'ajeno_recortado.mp3', 'input_path' => 'conversions/x/original.mp3',
        'actor_name' => 'Otro', 'ip_address' => '10.0.0.9', 'session_hash' => hash('sha256', 'otra-sesion'), 'expires_at' => now()->addHour(),
    ]);

    expect(fn () => $page->set('lastToken', $foreign->token))->toThrow(CannotUpdateLockedPropertyException::class);
    $page->assertDontSee('ajeno_recortado');
});

it('asks for the name in plain words and marks the error for the first step', function () {
    $content = conversionAudio('wav', 3);
    Livewire::test(PublicFileConverterPage::class)->call('selectFormat', 'audiocut')
        ->set('file', UploadedFile::fake()->createWithContent('tono.wav', $content)->mimeType((new finfo(FILEINFO_MIME_TYPE))->buffer($content)))
        ->set('options.segments', '[[0.5,1.5]]')
        ->call('convert')
        ->assertHasErrors('actorName')
        ->assertSeeHtml('data-ac-error="1"')
        ->assertSee('El campo nombre es obligatorio.')
        ->assertDontSee('actor name');
})->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed');
