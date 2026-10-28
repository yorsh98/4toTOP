<x-in-layout title="Documentos">
    <x-public.list-page
        title="Documentos"
        lead="Formularios y documentos para descargar. Se abren en otra pestaña."
        :total="count($doc)"
        :noun="['documento', 'documentos']"
        placeholder="Filtrar documentos"
        empty="Aún no hay documentos públicos. Se publican desde el backoffice, en Documentos.">
        <x-public.document-list :items="$doc" icon="fa-file-lines" />
    </x-public.list-page>
</x-in-layout>
