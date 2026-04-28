<x-slot:header>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panel Administrativo de los Turnos</h2>
</x-slot:header>

<div class="mx-auto max-w-6xl space-y-6 px-3 py-4 sm:px-6 lg:px-8" data-turno-page="1">
    <div class="rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50 to-indigo-50 p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Flujo recomendado</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-900">Configura Turno 1 y envia la tabla semanal</h3>
                <p class="mt-1 text-sm text-gray-700">
                    1) Completa nombres y funciones, 2) Define la semana en formato libre, 3) Guarda Turno 1, 4) Envia tabla.
                </p>
            </div>

            <button
                type="button"
                wire:click="sendTurno1Summary"
                wire:loading.attr="disabled"
                wire:target="sendTurno1Summary"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="sendTurno1Summary">Enviar tabla Turno 1 ahora</span>
                <span wire:loading wire:target="sendTurno1Summary">Encolando envio...</span>
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if (!empty($lastTurnoSummaryIssue['reason'] ?? null))
        <div
            x-data="{
                issueId: {{ (int) ($lastTurnoSummaryIssue['id'] ?? 0) }},
                visible: true,
                init() {
                    this.visible = !window.turnoIssueState.isDismissed(this.issueId);
                },
                dismiss() {
                    window.turnoIssueState.dismiss(this.issueId);
                    this.visible = false;
                }
            }"
            x-show="visible"
            data-turno-issue-id="{{ (int) ($lastTurnoSummaryIssue['id'] ?? 0) }}"
            class="flex items-start justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
        >
            <p>Ultimo envio de tabla Turno 1 con observaciones: {{ $lastTurnoSummaryIssue['reason'] }}</p>
            <button
                type="button"
                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded text-amber-700 hover:bg-amber-100 hover:text-amber-900"
                x-on:click="dismiss"
                aria-label="Cerrar notificacion de Turno 1"
                title="Cerrar"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (!empty($lastTurno2AcdSummaryIssue['reason'] ?? null))
        <div
            x-data="{
                issueId: {{ (int) ($lastTurno2AcdSummaryIssue['id'] ?? 0) }},
                visible: true,
                init() {
                    this.visible = !window.turnoIssueState.isDismissed(this.issueId);
                },
                dismiss() {
                    window.turnoIssueState.dismiss(this.issueId);
                    this.visible = false;
                }
            }"
            x-show="visible"
            data-turno-issue-id="{{ (int) ($lastTurno2AcdSummaryIssue['id'] ?? 0) }}"
            class="flex items-start justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
        >
            <p>Ultimo envio de tabla Turno 2 ACD con observaciones: {{ $lastTurno2AcdSummaryIssue['reason'] }}</p>
            <button
                type="button"
                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded text-amber-700 hover:bg-amber-100 hover:text-amber-900"
                x-on:click="dismiss"
                aria-label="Cerrar notificacion de Turno 2 ACD"
                title="Cerrar"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-gray-900">Turno 1 - Despacho semanal</h3>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Principal para tabla automatizada</span>
            </div>
        </div>

        <div class="space-y-6 p-5">
            <form wire:submit="saveTurno1" class="space-y-6">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-700">Semana del despacho</h4>
                    <label for="semanaTurno1" class="block text-sm font-medium text-gray-600">Rango semanal (formato libre)</label>
                    <input
                        type="text"
                        id="semanaTurno1"
                        wire:model="semanaTurno1"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Ej: 13-04-2026 al 17-04-2026"
                    >
                    <p class="mt-2 text-xs text-gray-500">Este texto se usa en el encabezado de la tabla que se envia por correo.</p>
                    @error('semanaTurno1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-700">Juez del turno</h4>
                    <div class="grid gap-3 md:grid-cols-3">
                        @for ($i = 1; $i <= 3; $i++)
                            @php $field = 'TM'.$i; @endphp
                            <div>
                                <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                                <input
                                    type="text"
                                    id="{{ $field }}"
                                    wire:model="turno1.{{ $field }}"
                                    list="magistradosList"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Escriba o seleccione magistrado"
                                >
                                @error('turno1.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-700">Funcionarios del equipo</h4>
                    <div class="grid gap-4 lg:grid-cols-3">
                        @for ($i = 1; $i <= 3; $i++)
                            @php
                                $field = 'TF'.$i;
                                $cargoField = $field.'_CARGO';
                            @endphp
                            <div class="space-y-2 rounded-md border border-gray-200 p-3">
                                <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Funcionario {{ $i }}</label>
                                <input
                                    type="text"
                                    id="{{ $field }}"
                                    wire:model="turno1.{{ $field }}"
                                    list="funcionariosList"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Nombre del funcionario"
                                >
                                @error('turno1.'.$field) <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                                <label for="{{ $cargoField }}" class="block text-sm font-medium text-gray-600">Funcion de equipo {{ $i }}</label>
                                <input
                                    type="text"
                                    id="{{ $cargoField }}"
                                    wire:model="turno1.{{ $cargoField }}"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Ej: Encargado de Acta"
                                >
                                @error('turno1.'.$cargoField) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Jefatura</h4>
                        <span class="text-xs text-gray-500">La observacion en la tabla se envia fija como "Jefe de Turno"</span>
                    </div>
                    <div class="grid gap-3 md:grid-cols-3">
                        @for ($i = 1; $i <= 3; $i++)
                            @php $field = 'TJ'.$i; @endphp
                            <div>
                                <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Jefatura {{ $i }}</label>
                                <input
                                    type="text"
                                    id="{{ $field }}"
                                    wire:model="turno1.{{ $field }}"
                                    list="jefaturasList"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Escriba o seleccione jefatura"
                                >
                                @error('turno1.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                        wire:loading.attr="disabled"
                        wire:target="saveTurno1"
                    >
                        <span wire:loading.remove wire:target="saveTurno1">Guardar Turno 1</span>
                        <span wire:loading wire:target="saveTurno1">Guardando Turno 1...</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <details class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <summary class="cursor-pointer select-none px-5 py-4 text-base font-semibold text-gray-800">Turno 2 ACD</summary>
        <div class="space-y-4 border-t border-gray-200 p-5">
            <div class="flex justify-end">
                <button
                    type="button"
                    wire:click="sendTurno2AcdSummary"
                    wire:loading.attr="disabled"
                    wire:target="sendTurno2AcdSummary"
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="sendTurno2AcdSummary">Enviar tabla Turno 2 ACD ahora</span>
                    <span wire:loading wire:target="sendTurno2AcdSummary">Encolando envio...</span>
                </button>
            </div>

            <form wire:submit="saveTurno2Acd" class="space-y-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-700">Periodo del turno 2 ACD</h4>
                    <label for="semanaTurnoAcd" class="block text-sm font-medium text-gray-600">Rango de periodo (cada 3 meses)</label>
                    <input
                        type="text"
                        id="semanaTurnoAcd"
                        wire:model="periodoTurno2Acd"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Ej: 13-04-2026 al 13-07-2026"
                    >
                    <p class="mt-2 text-xs text-gray-500">Este texto se usa en el encabezado de la tabla de Turno 2 ACD enviada por correo.</p>
                    @error('periodoTurno2Acd') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    @for ($i = 1; $i <= 3; $i++)
                        @php $field = 'ACDM'.$i; @endphp
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $field }}"
                                wire:model="turno2.{{ $field }}"
                                list="magistradosList"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Escriba o seleccione magistrado"
                            >
                            @error('turno2.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    @for ($i = 1; $i <= 3; $i++)
                        @php
                            $field = 'ACDF'.$i;
                            $cargoField = $field.'_CARGO';
                        @endphp
                        <div class="space-y-2 rounded-md border border-gray-200 p-3">
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Funcionario {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $field }}"
                                wire:model="turno2.{{ $field }}"
                                list="funcionariosList"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Escriba o seleccione funcionario"
                            >
                            @error('turno2.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                            <label for="{{ $cargoField }}" class="block text-sm font-medium text-gray-600">Funcion de equipo {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $cargoField }}"
                                wire:model="turno2.{{ $cargoField }}"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Ej: Encargado de Acta"
                            >
                            @error('turno2.'.$cargoField) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    @for ($i = 1; $i <= 3; $i++)
                        @php $field = 'ACDJ'.$i; @endphp
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Jefatura {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $field }}"
                                wire:model="turno2.{{ $field }}"
                                list="jefaturasList"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Escriba o seleccione jefatura"
                            >
                            @error('turno2.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                        wire:loading.attr="disabled"
                        wire:target="saveTurno2Acd"
                    >
                        <span wire:loading.remove wire:target="saveTurno2Acd">Guardar Turno 2</span>
                        <span wire:loading wire:target="saveTurno2Acd">Guardando Turno 2...</span>
                    </button>
                </div>
            </form>
        </div>
    </details>

    <details class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <summary class="cursor-pointer select-none px-5 py-4 text-base font-semibold text-gray-800">Turno 3 (PROGRAMACION DE AUDIENCIAS)</summary>
        <div class="space-y-4 border-t border-gray-200 p-5">
            <form wire:submit="saveTurno3" class="space-y-4">
                <div class="grid gap-3 md:grid-cols-3">
                    @for ($i = 1; $i <= 3; $i++)
                        @php
                            $field = 'TM'.$i;
                            $id = 'T3_'.$field;
                        @endphp
                        <div>
                            <label for="{{ $id }}" class="block text-sm font-medium text-gray-600">Turno {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $id }}"
                                wire:model="turno3.{{ $field }}"
                                list="magistradosList"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Escriba o seleccione magistrado"
                            >
                            @error('turno3.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                        wire:loading.attr="disabled"
                        wire:target="saveTurno3"
                    >
                        <span wire:loading.remove wire:target="saveTurno3">Guardar Turno 3</span>
                        <span wire:loading wire:target="saveTurno3">Guardando Turno 3...</span>
                    </button>
                </div>
            </form>
        </div>
    </details>

    <datalist id="magistradosList">
        @foreach ($magistrados as $nombre)
            <option value="{{ $nombre }}">
        @endforeach
    </datalist>

    <datalist id="funcionariosList">
        @foreach ($funcionarios as $nombre)
            <option value="{{ $nombre }}">
        @endforeach
    </datalist>

    <datalist id="jefaturasList">
        @foreach ($jefaturas as $nombre)
            <option value="{{ $nombre }}">
        @endforeach
    </datalist>

    <script>
        (function () {
            if (window.__turnoIssueUiBound) {
                return;
            }

            window.__turnoIssueUiBound = true;

            const prefix = 'turno_issue_dismissed_';

            window.turnoIssueState = {
                isDismissed(issueId) {
                    if (!issueId) {
                        return false;
                    }

                    try {
                        return window.sessionStorage.getItem(prefix + issueId) === '1';
                    } catch (_) {
                        return false;
                    }
                },
                dismiss(issueId) {
                    if (!issueId) {
                        return;
                    }

                    try {
                        window.sessionStorage.setItem(prefix + issueId, '1');
                    } catch (_) {}
                },
            };

            document.addEventListener('livewire:navigating', () => {
                if (!document.querySelector('[data-turno-page="1"]')) {
                    return;
                }

                document.querySelectorAll('[data-turno-issue-id]').forEach((element) => {
                    const issueId = Number(element.getAttribute('data-turno-issue-id') || 0);
                    window.turnoIssueState.dismiss(issueId);
                });
            });
        })();
    </script>
</div>
