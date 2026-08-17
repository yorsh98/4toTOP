@foreach (['ok' => 'success', 'success' => 'success', 'info' => 'info', 'warning' => 'warning', 'error' => 'error'] as $key => $icon)
    @if (session($key))
        <span data-flash-notification data-icon="{{ $icon }}" data-message="{{ session($key) }}" hidden></span>
    @endif
@endforeach
