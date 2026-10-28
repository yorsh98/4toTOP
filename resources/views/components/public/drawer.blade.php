@props(['id', 'title', 'lead' => null])

<div class="fo-scrim cm-scrim" data-fo-close="{{ $id }}" aria-hidden="true"></div>
<aside id="fo-drawer-{{ $id }}" class="fo-drawer cm-drawer" role="dialog" aria-modal="true"
       aria-labelledby="fo-drawer-{{ $id }}-title" inert>
    <header class="fo-drawer-head">
        <div>
            <h2 id="fo-drawer-{{ $id }}-title" class="fo-drawer-title">{{ $title }}</h2>
            @if ($lead)
                <p class="fo-drawer-lead">{{ $lead }}</p>
            @endif
        </div>
        <button type="button" class="fo-icon-btn cm-press" data-fo-close="{{ $id }}" aria-label="Cerrar">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </header>
    <div class="fo-drawer-body">
        {{ $slot }}
    </div>
</aside>
