<?php

namespace App\Livewire\Concerns;

use App\Conversions\ConversionTool;
use App\Models\FileConversion;
use App\Services\AudioInspector;
use App\Services\FileConversionService;
use App\Services\PdfInspector;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The page of /convertir-archivos (frontoffice) and its backoffice twin. Each tool lives in App\Conversions;
 * this trait handles uploads, the options form (options.*) and queueing.
 */
trait ConvertsFiles
{
    use WithFileUploads;

    #[Url(as: 'herramienta')]
    public string $format = 'pdf';

    public string $actorName = '';

    public $file;

    /** Tools that take several files: the files in order, uploaded one at a time through $incomingFile. */
    public array $files = [];

    /** Page count of each file in $files (null for images). */
    public array $filePages = [];

    public $incomingFile;

    /** Page count of the uploaded PDF, read as soon as it arrives so ranges can be checked. */
    public ?int $pageCount = null;

    public ?float $audioDuration = null;

    public array $options = [];

    protected function area(): string
    {
        return 'frontoffice';
    }

    public function mountConvertsFiles(): void
    {
        if (! array_key_exists($this->format, ConversionTool::available())) {
            $this->format = 'pdf';
        }
        $this->options = $this->tool()->defaults();
    }

    protected function tool(): ConversionTool
    {
        return ConversionTool::for($this->format);
    }

    public function selectFormat(string $format): void
    {
        if (! array_key_exists($format, ConversionTool::available())) {
            return;
        }

        $this->format = $format;
        $this->reset('file', 'files', 'filePages', 'incomingFile', 'pageCount', 'audioDuration');
        $this->options = $this->tool()->defaults();
        $this->resetValidation();
    }

    /** @return list<mixed> */
    protected function fileRules(): array
    {
        return [File::types($this->tool()->config['accept'])->max($this->tool()->config['max_kb'])];
    }

    public function updatedFile(): void
    {
        $this->pageCount = null;
        $this->audioDuration = null;
        if ($this->tool()->needsAudioProbe()) {
            $this->options['segments'] = '[]';
            $this->validate(['file' => ['required', ...$this->fileRules()]]);
            try {
                $inspector = app(AudioInspector::class);
                $duration = $inspector->probe($this->file->getRealPath())['duration'];
                $peaks = $inspector->peaks($this->file->getRealPath(), $duration);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['file' => $exception->getMessage()]);
            } catch (\RuntimeException $exception) {
                report($exception);
                throw ValidationException::withMessages(['file' => 'No se pudo analizar el audio. Vuelve a subirlo e inténtalo de nuevo.']);
            }
            $this->audioDuration = $duration;
            $this->dispatch('audio-loaded', duration: $duration, perSecond: $peaks['per_second'], peaks: $peaks['peaks'], name: $this->file->getClientOriginalName());

            return;
        }
        if (! $this->tool()->needsPageCount()) {
            return;
        }
        $this->validate(['file' => ['required', ...$this->fileRules()]]);
        $this->pageCount = $this->readPages($this->file, 'file');
    }

    public function updatedIncomingFile(): void
    {
        $tool = $this->tool();
        if (count($this->files) >= $tool->config['max_files']) {
            $this->reset('incomingFile');
            throw ValidationException::withMessages(['files' => 'Puedes agregar hasta '.$tool->config['max_files'].' archivos.']);
        }

        $this->validate(['incomingFile' => ['required', ...$this->fileRules()]], [], ['incomingFile' => 'archivo']);
        $this->filePages[] = $tool->acceptsPdf() ? $this->readPages($this->incomingFile, 'incomingFile') : null;
        $this->files[] = $this->incomingFile;
        $this->reset('incomingFile');
    }

    public function moveFile(int $index, int $offset): void
    {
        $target = $index + $offset;
        if (! isset($this->files[$index], $this->files[$target])) {
            return;
        }

        [$this->files[$index], $this->files[$target]] = [$this->files[$target], $this->files[$index]];
        [$this->filePages[$index], $this->filePages[$target]] = [$this->filePages[$target], $this->filePages[$index]];
    }

    public function removeFile(int $index): void
    {
        array_splice($this->files, $index, 1);
        array_splice($this->filePages, $index, 1);
    }

    public function sortFilesByName(): void
    {
        $order = array_keys($this->files);
        usort($order, fn (int $a, int $b) => strnatcasecmp($this->files[$a]->getClientOriginalName(), $this->files[$b]->getClientOriginalName()));
        $this->files = array_map(fn (int $index) => $this->files[$index], $order);
        $this->filePages = array_map(fn (int $index) => $this->filePages[$index], $order);
    }

    public function convert(FileConversionService $service): void
    {
        $this->authorizeConversion();
        $tool = $this->tool();
        if (! array_key_exists($this->format, ConversionTool::available())) {
            throw ValidationException::withMessages(['format' => 'Esta herramienta no está disponible.']);
        }

        $min = $tool->config['min_files'] ?? 1;
        $this->validate([
            'actorName' => $this->area() === 'frontoffice' ? ['required', 'string', 'min:2', 'max:120'] : ['nullable'],
            ...($tool->multiple() ? [
                'files' => ['required', 'array', 'min:'.$min, 'max:'.$tool->config['max_files']],
                'files.*' => $this->fileRules(),
            ] : ['file' => ['required', ...$this->fileRules()]]),
            ...collect($tool->rules())->mapWithKeys(fn ($rules, $key) => ['options.'.$key => $rules])->all(),
        ], ['files.min' => $min === 1 ? 'Agrega al menos un archivo.' : "Agrega al menos {$min} archivos."]);

        $files = $tool->multiple() ? array_values($this->files) : [$this->file];
        if ($tool->multiple() && array_sum(array_map(fn ($file) => $file->getSize(), $files)) > $tool->config['max_total_kb'] * 1024) {
            throw ValidationException::withMessages(['files' => 'Los archivos suman más de '.($tool->config['max_total_kb'] / 1024).' MB.']);
        }
        if ($tool->needsPageCount()) {
            $this->pageCount ??= $this->readPages($this->file, 'file');
        }
        $options = $tool->prepare($this->options, $files, $this->pageCount);

        if ($this->area() === 'frontoffice') {
            $key = 'convert:'.request()->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) {
                throw ValidationException::withMessages(['file' => 'Demasiadas solicitudes desde esta red. Vuelve a intentarlo más tarde.']);
            }
            RateLimiter::hit($key, 3600);
        }

        $service->create($files, $this->format, $this->area(), $this->area() === 'frontoffice' ? trim($this->actorName) : auth()->user()->name, $options);
        $this->reset('file', 'files', 'filePages', 'pageCount', 'audioDuration');
        $this->options = $tool->defaults();
        $this->dispatch('conversion-queued');
    }

    private function readPages($file, string $field): int
    {
        try {
            return app(PdfInspector::class)->pages($file->getRealPath());
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([$field => $exception->getMessage()]);
        }
    }

    protected function authorizeConversion(): void {}

    /** The tool's own limit, capped by what PHP accepts (upload_max_filesize / post_max_size). */
    protected function maxUploadBytes(): int
    {
        return (int) min($this->tool()->config['max_kb'] * 1024, UploadedFile::getMaxFilesize());
    }

    public function render()
    {
        $this->authorizeConversion();
        $sessionHash = hash('sha256', session()->getId());
        $jobs = FileConversion::query()
            ->when($this->area() === 'frontoffice', fn ($query) => $query->where('area', 'frontoffice')->where('session_hash', $sessionHash))
            ->when($this->area() === 'backoffice', fn ($query) => $query->where('area', 'backoffice')->where('user_id', auth()->id()))
            ->latest()->limit(10)->get();
        $tools = ConversionTool::available();

        return view('livewire.file-converter', [
            'area' => $this->area(),
            'jobs' => $jobs,
            'tools' => $tools,
            'tool' => $this->tool(),
            'maxUploadBytes' => $this->maxUploadBytes(),
            'remainingByFormat' => $this->area() === 'frontoffice' ? array_map(
                fn (ConversionTool $tool) => max(0, config('conversions.public_limit') - app(FileConversionService::class)->used($sessionHash, $tool->format)),
                $tools,
            ) : [],
        ]);
    }
}
