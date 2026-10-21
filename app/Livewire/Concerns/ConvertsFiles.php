<?php

namespace App\Livewire\Concerns;

use App\Models\FileConversion;
use App\Services\FileConversionService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait ConvertsFiles
{
    use WithFileUploads;

    public string $format = 'pdf';

    public string $actorName = '';

    public $file;

    protected function area(): string
    {
        return 'frontoffice';
    }

    public function selectFormat(string $format): void
    {
        if (! in_array($format, ['pdf', 'm4a'], true)) {
            return;
        }

        $this->format = $format;
        $this->reset('file');
        $this->resetValidation();
    }

    public function convert(FileConversionService $service): void
    {
        $this->authorizeConversion();

        $validated = $this->validate([
            'format' => ['required', 'in:pdf,m4a'],
            'actorName' => $this->area() === 'frontoffice' ? ['required', 'string', 'min:2', 'max:120'] : ['nullable'],
            'file' => ['required', File::types([$this->format])->max(config($this->format === 'pdf' ? 'conversions.pdf_max_kb' : 'conversions.audio_max_kb'))],
        ]);

        if ($this->area() === 'frontoffice') {
            $key = 'convert:'.request()->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) {
                throw ValidationException::withMessages(['file' => 'Demasiadas solicitudes desde esta red. Vuelve a intentarlo más tarde.']);
            }
            RateLimiter::hit($key, 3600);
        }

        $service->create($validated['file'], $validated['format'], $this->area(), $this->area() === 'frontoffice' ? trim($validated['actorName']) : auth()->user()->name);
        $this->reset('file');
    }

    protected function authorizeConversion(): void {}

    /** The converter's own limit, capped by what PHP accepts (upload_max_filesize / post_max_size). */
    protected function maxUploadBytes(): int
    {
        $converterLimit = config($this->format === 'pdf' ? 'conversions.pdf_max_kb' : 'conversions.audio_max_kb') * 1024;

        return (int) min($converterLimit, UploadedFile::getMaxFilesize());
    }

    public function render()
    {
        $this->authorizeConversion();
        $sessionHash = hash('sha256', session()->getId());
        $jobs = FileConversion::query()
            ->when($this->area() === 'frontoffice', fn ($query) => $query->where('area', 'frontoffice')->where('session_hash', $sessionHash))
            ->when($this->area() === 'backoffice', fn ($query) => $query->where('area', 'backoffice')->where('user_id', auth()->id()))
            ->latest()->limit(10)->get();

        return view('livewire.file-converter', [
            'area' => $this->area(),
            'jobs' => $jobs,
            'maxUploadBytes' => $this->maxUploadBytes(),
            'remainingByFormat' => $this->area() === 'frontoffice' ? [
                'pdf' => max(0, config('conversions.public_limit') - app(FileConversionService::class)->used($sessionHash, 'pdf')),
                'm4a' => max(0, config('conversions.public_limit') - app(FileConversionService::class)->used($sessionHash, 'm4a')),
            ] : [],
        ]);
    }
}
