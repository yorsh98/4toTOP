<x-in-layout title="Procedimientos del tribunal">
    <x-public.list-page
        title="Procedimientos del tribunal"
        lead="Procedimientos internos para consultar. Se abren en otra pestaña."
        :total="count($procedimientos)"
        :noun="['procedimiento', 'procedimientos']"
        placeholder="Filtrar procedimientos"
        empty="Aún no hay procedimientos públicos. Se publican desde el backoffice, en Documentos.">
        <x-public.document-list :items="$procedimientos" icon="fa-list-check" />
    </x-public.list-page>
</x-in-layout>
