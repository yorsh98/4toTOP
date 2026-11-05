<?php

namespace App\Livewire;

use App\Models\FileConversion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class FileConversionHistoryPage extends Component
{
    use WithPagination;

    public string $area = '';

    public string $format = '';

    public string $status = '';

    public string $date = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('conversiones.ver_registro');

        return view('livewire.file-conversion-history-page', [
            'jobs' => FileConversion::query()
                ->when(in_array($this->area, ['frontoffice', 'backoffice'], true), fn ($query) => $query->where('area', $this->area))
                ->when(array_key_exists($this->format, config('conversions.tools')), fn ($query) => $query->where('format', $this->format))
                ->when(in_array($this->status, ['queued', 'processing', 'ready', 'failed'], true), fn ($query) => $query->where('status', $this->status))
                ->when(Validator::make(['date' => $this->date], ['date' => 'required|date_format:Y-m-d'])->passes(), fn ($query) => $query->whereDate('created_at', $this->date))
                ->latest()->paginate(25),
        ]);
    }
}
