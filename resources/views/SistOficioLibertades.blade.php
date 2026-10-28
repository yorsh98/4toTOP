@php
    $tipo = old('tipo', session('tipo', 'oficio')) === 'libertad' ? 'libertad' : 'oficio';
    $numero = session('NumEntregado');
    $anio = session('año');
@endphp

<x-public.layout title="Oficios y libertades">
    <div class="fo-page fo-oficios" data-app-view>
        <header class="fo-page-head cm-welcome">
            <a href="{{ route('welcome') }}" class="fo-back" data-fo-link style="--cm-i: 0">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Inicio
            </a>
            <h1 class="fo-page-title" style="--cm-i: 1">Oficios y libertades</h1>
            <p class="fo-page-lead" style="--cm-i: 2">Pide un número correlativo para una causa. No necesitas iniciar sesión.</p>
        </header>

        <div class="fo-oficios-grid">
            <div class="fo-panel cm-rise">
                @if (session('success') && $numero)
                    <section class="fo-ticket" aria-live="polite" aria-labelledby="fo-ticket-title">
                        <p class="fo-ticket-label" id="fo-ticket-title">
                            {{ session('tipo') === 'libertad' ? 'Número de libertad asignado' : 'Número de oficio asignado' }}
                        </p>
                        <p class="fo-ticket-number fo-num cm-bump">{{ $numero }}<span>-{{ $anio }}</span></p>
                        <p class="fo-ticket-note">Anótalo en el documento. Quedó registrado con la causa y el solicitante.</p>
                        <button type="button" class="fo-btn fo-btn--ghost cm-press" data-fo-copy="{{ $numero }}-{{ $anio }}">
                            <i class="fa-regular fa-copy" aria-hidden="true"></i> <span data-fo-copy-label>Copiar número</span>
                        </button>
                    </section>
                @endif

                @if (session('error') || $errors->any())
                    <div class="fo-alert" role="alert">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                        <div>
                            <p>{{ session('error') ?? 'No se pudo pedir el número. Corrige los campos marcados.' }}</p>
                            @if ($errors->any())
                                <ul>
                                    @foreach ($errors->all() as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endif

                <form class="fo-form" action="{{ route('SistOficioLibertades.store') }}" method="POST" data-fo-oficios>
                    @csrf

                    <fieldset class="fo-seg cm-pill-track" data-fo-seg>
                        <legend class="fo-label">Tipo de número</legend>
                        <span class="cm-pill fo-seg-pill" aria-hidden="true"></span>
                        @foreach (['oficio' => 'Oficio', 'libertad' => 'Libertad'] as $value => $label)
                            <label class="fo-seg-option">
                                <input type="radio" name="tipo" value="{{ $value }}" @checked($tipo === $value)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </fieldset>

                    <div class="fo-field">
                        <label class="fo-label" for="CausaAsig">Causa asignada</label>
                        <input class="fo-input fo-num cm-focus-ring @error('CausaAsig') is-invalid @enderror" type="text" id="CausaAsig" name="CausaAsig"
                               value="{{ old('CausaAsig') }}" required maxlength="10" pattern="[a-zA-Z0-9\s\-]{1,10}"
                               autocomplete="off" aria-describedby="CausaAsig-hint" placeholder="Ej.: 123-2026">
                        <p class="fo-hint" id="CausaAsig-hint">Hasta 10 caracteres: letras, números, espacios o guion.</p>
                    </div>

                    <div class="fo-field">
                        <label class="fo-label" for="UserSolicitante">Solicitante</label>
                        <select class="fo-select cm-focus-ring @error('UserSolicitante') is-invalid @enderror" id="UserSolicitante" name="UserSolicitante" required>
                            <option value="" disabled @selected(! old('UserSolicitante'))>Elige quién lo pide</option>
                            @foreach ($solicitantes as $solicitante)
                                <option value="{{ $solicitante->nombre }}" @selected(old('UserSolicitante') === $solicitante->nombre)>{{ $solicitante->nombre }}</option>
                            @endforeach
                        </select>
                        @if ($solicitantes->isEmpty())
                            <p class="fo-hint fo-hint--warn">No hay solicitantes habilitados. Pide a un administrador que los active en el backoffice.</p>
                        @endif
                    </div>

                    <div class="fo-field">
                        <label class="fo-label" for="UserDirigido">Motivo</label>
                        <input class="fo-input cm-focus-ring @error('UserDirigido') is-invalid @enderror" type="text" id="UserDirigido" name="UserDirigido"
                               value="{{ old('UserDirigido') }}" required pattern="[a-zA-Z0-9\s\-]+" autocomplete="off"
                               aria-describedby="UserDirigido-hint">
                        <p class="fo-hint" id="UserDirigido-hint">Solo letras, números, espacios o guion, sin tildes.</p>
                    </div>

                    <button type="submit" class="fo-btn fo-btn--primary cm-press" data-fo-submit>
                        <span data-fo-submit-label>Pedir número de {{ $tipo }}</span>
                    </button>
                </form>
            </div>

            <aside class="fo-oficios-aside cm-welcome" aria-label="Registros">
                <h2 class="fo-aside-title" style="--cm-i: 0">Números ya entregados</h2>
                <a href="{{ url('/tablas') }}" class="fo-access fo-access--row cm-lift cm-press" style="--cm-i: 1">
                    <span class="fo-access-icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></span>
                    <span class="fo-access-text">
                        <span class="fo-access-title">Últimos oficios</span>
                        <span class="fo-access-desc">Registro de oficios del año.</span>
                    </span>
                </a>
                <a href="{{ route('tabla2') }}" class="fo-access fo-access--row cm-lift cm-press" style="--cm-i: 2">
                    <span class="fo-access-icon" aria-hidden="true"><i class="fa-solid fa-door-open"></i></span>
                    <span class="fo-access-text">
                        <span class="fo-access-title">Últimas libertades</span>
                        <span class="fo-access-desc">Registro de libertades del año.</span>
                    </span>
                </a>
            </aside>
        </div>
    </div>
</x-public.layout>
