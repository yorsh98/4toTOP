<x-in-layout title="Buscador de sentencias">
    <header class="fo-page-head fo-list-head">
        <a href="{{ route('welcome') }}" class="fo-back" data-fo-link>
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Inicio
        </a>
        <h1 class="fo-page-title">Buscador de sentencias</h1>
        <p class="fo-page-lead">Índice de sentencias del 4º TOP, desde 2024 a la fecha.</p>
    </header>

    <livewire:buscador-sentencias-tabla />
</x-in-layout>
