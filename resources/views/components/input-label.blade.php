@props(['value'])

<label {{ $attributes->merge(['class' => 'bo-label']) }}>
    {{ $value ?? $slot }}
</label>
