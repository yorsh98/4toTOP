@props(['items', 'icon' => 'fa-file-lines'])

@php
    $formats = [
        'pdf' => 'PDF',
        'doc' => 'Word', 'docx' => 'Word', 'odt' => 'Word',
        'xls' => 'Excel', 'xlsx' => 'Excel', 'ods' => 'Excel', 'csv' => 'Excel',
        'ppt' => 'PowerPoint', 'pptx' => 'PowerPoint',
        'jpg' => 'Imagen', 'jpeg' => 'Imagen', 'png' => 'Imagen', 'webp' => 'Imagen',
        'zip' => 'ZIP',
    ];
    $format = function (string $url) use ($formats): ?string {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

        return $ext === '' ? null : ($formats[$ext] ?? strtoupper($ext));
    };
@endphp

<ul class="fo-rows">
    @foreach ($items as $item)
        @php($kind = $format($item['url']))
        <x-public.list-row
            :title="$item['nombre']"
            :href="$item['url']"
            :icon="$icon"
            :keywords="$kind"
            :badges="$kind ? [['label' => $kind, 'class' => 'fo-badge--format']] : []" />
    @endforeach
</ul>
