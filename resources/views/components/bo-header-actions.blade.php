<div class="bo-header-actions">
    <livewire:insumos-notification-bell />
    <a href="{{ route('welcome') }}" class="bo-header-btn cm-press" title="Ver sitio público" aria-label="Ver sitio público"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
    <button type="button" x-data @click="$store.theme.toggle(); $nextTick(() => window.boSidebar?.spin($el.querySelector('i')))" class="bo-header-btn cm-press" title="Cambiar tema" aria-label="Cambiar tema"><i class="fa-solid" :class="$store.theme.value === 'dark' ? 'fa-sun' : 'fa-moon'"></i></button>
</div>
