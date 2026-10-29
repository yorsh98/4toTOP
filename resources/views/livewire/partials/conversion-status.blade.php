@if ($job->status === 'queued')
    En cola
    @if ($queueBefore[$job->id] > 0) · {{ $queueBefore[$job->id] }} archivo(s) antes
    @endif
@else
    {{ $job->stage ?: 'Procesando…' }}
    @if ($job->progress !== null)
        <span class="{{ $prefix }}-progress" role="progressbar" aria-valuenow="{{ $job->progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso de conversión">
            <span style="width: {{ $job->progress }}%"></span><span class="sr-only">{{ $job->progress }}%</span>
        </span>
    @endif
@endif
