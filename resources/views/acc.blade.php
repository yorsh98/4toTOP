<x-in-layout title="Accesos directos">
    <x-public.list-page
        title="Accesos directos"
        lead="Sistemas y sitios de uso frecuente. Se abren en otra pestaña."
        :meta="$grupos->flatten()->contains(fn ($a) => $a->soloRedInterna()) ? 'Los marcados «Solo red PJUD» abren solo desde la red del Poder Judicial.' : null"
        :total="$total"
        :noun="['acceso', 'accesos']"
        placeholder="Buscar un sistema o sitio"
        empty="Aún no hay accesos publicados. Se agregan desde el backoffice, en Documentos.">
        @if ($grupos->count() > 1)
            <x-slot:toolbar>
                <nav class="fo-chips" aria-label="Grupos de accesos">
                    @foreach ($grupos as $grupo => $accesos)
                        <a href="#grupo-{{ Str::slug($grupo) }}" class="fo-chip cm-press">{{ $grupo }} <span class="fo-num">{{ $accesos->count() }}</span></a>
                    @endforeach
                </nav>
            </x-slot:toolbar>
        @endif

        @foreach ($grupos as $grupo => $accesos)
            <section class="fo-list-group" id="grupo-{{ Str::slug($grupo) }}" data-fo-group aria-labelledby="grupo-{{ Str::slug($grupo) }}-title">
                <h2 class="fo-list-group-title" id="grupo-{{ Str::slug($grupo) }}-title">
                    {{ $grupo }} <span class="fo-num">{{ $accesos->count() }}</span>
                </h2>
                <ul class="fo-rows fo-rows--grid">
                    @foreach ($accesos as $acceso)
                        <x-public.list-row
                            :title="$acceso->nombre"
                            :href="$acceso->url"
                            :meta="$acceso->host()"
                            :keywords="$grupo"
                            :inline-badges="true"
                            :badges="$acceso->soloRedInterna() ? [['label' => 'Solo red PJUD', 'class' => 'fo-badge--net', 'title' => 'Abre solo desde la red del Poder Judicial']] : []" />
                    @endforeach
                </ul>
            </section>
        @endforeach
    </x-public.list-page>
</x-in-layout>
