<x-in-layout>
@php
    $tz = 'America/Santiago';
    $hoyStr = \Carbon\Carbon::now($tz)->toDateString();
    $prevStr = \Carbon\Carbon::parse($fecha, $tz)->subDay()->toDateString();
    $nextStr = \Carbon\Carbon::parse($fecha, $tz)->addDay()->toDateString();
@endphp

<style>
    .ttypp-view .toolbar .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px 10px;
        font-weight: 600;
        text-decoration: none;
        color: #111827;
    }
    .ttypp-view .toolbar input[type="date"] {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px 10px;
        font-weight: 600;
    }
</style>

<div class="grid gap-6 pt-5 mb-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__backInUp animate__delay-0_3s ttypp-view">
    <div class="absolute top-4 left-4">
        <a href="{{ url('/') }}" class="bg-white text-black font-semibold py-1 px-3 rounded-md shadow-md flex items-center text-sm hover:bg-blue-700 hover:text-white transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
            INICIO
        </a>
    </div>

    <div class="flex justify-center items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 lg:pb-10">
        <div class="pt-3 sm:pt-5 text-center">
            <h2 class="text-xl font-semibold text-black">TTyPP</h2>
            <p class="mt-2 text-sm text-slate-600">Unidad de Testigos y Peritos - {{ $fechaHuman }}</p>
            <div class="toolbar mt-4 flex flex-wrap items-center justify-center gap-2">
                <a class="btn" href="{{ url()->current() }}?fecha={{ $prevStr }}">Anterior</a>
                <form method="GET" action="{{ url()->current() }}" class="inline-flex items-center gap-2">
                    <input type="date" name="fecha" value="{{ $fecha }}">
                    <button type="submit" class="btn">Ver</button>
                </form>
                <a class="btn" href="{{ url()->current() }}?fecha={{ $hoyStr }}">Hoy</a>
                <a class="btn" href="{{ url()->current() }}?fecha={{ $nextStr }}">Siguiente</a>
            </div>
        </div>
    </div>

    <div class="rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] overflow-x-auto">
        <h3 class="mb-4 text-lg font-semibold text-black">Listado TTYPP</h3>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">RIT</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">RUC</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Tipo AUD</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">N Dias</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Cant.</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Modalidad</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Encargada TI MP</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Encargada TI D</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Encargada Zoom</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Acta</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Sala</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Jueces</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($registros as $registro)
                    <tr>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->rit ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->ruc ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->tipo_audiencia ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->numero_dias ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->cantidad ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->modalidad ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->encargada_ti_mp ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->encargada_ti_d ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->encargada_zoom ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->acta ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->sala ?? '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $registro->jueces ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="px-3 py-8 text-center text-slate-500">No hay registros TTYPP para la fecha seleccionada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-in-layout>
