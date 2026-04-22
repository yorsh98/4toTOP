@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-blue-300 text-start text-base font-medium text-blue-100 bg-blue-300/15 focus:outline-none focus:text-blue-50 focus:bg-blue-300/20 focus:border-blue-200 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-200 hover:text-white hover:bg-slate-800/60 hover:border-slate-500 focus:outline-none focus:text-white focus:bg-slate-800/60 focus:border-slate-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
