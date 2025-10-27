<x-sist-layout>
  <style>
    /* === Flip en la TARJETA AZUL === */
    #blueCard {
      perspective: 500px;
      position: relative;        /* contexto para faces absolutas */
      overflow: hidden;          /* oculta scroll si las caras exceden */
    }
    #blueCard .flip-inner {
      position: relative;
      width: 100%;
      min-height: 460px;         /* alto mínimo del panel */
      transition: transform .6s ease;
      transform-style: preserve-3d;
    }
    #blueCard.flipped .flip-inner {
      transform: rotateY(180deg);
    }

    #blueCard .face {
      position: absolute; inset: 0;
      -webkit-backface-visibility: hidden; backface-visibility: hidden;
      padding: 1.25rem;
      display: flex; flex-direction: column;
    }
    #blueCard .back { transform: rotateY(180deg); }

    /* Si contenido crece, que scrollee dentro de la cara, no fuera del card */
    #blueCard .face { overflow: auto; }
  </style>

  <!-- Contenedor normal (NO gira) -->
  <main class="max-w-[680px] w-full mx-auto px-4 py-5">

    <!-- TARJETA AZUL que gira -->
    <div id="blueCard"
         class="border-3 rounded-[15px] p-1 bg-[#95b9d3] max-w-[600px] w-full mx-auto text-center">

      <div class="flip-inner">
        <!-- Cara FRONTAL (botones) -->
        <section class="face">
          <div class="mb-3">
            <h1 class="text-3xl fw-bold">SOLICITUD PARA NÚMEROS</h1>
            <h1 class="text-3xl fw-bold">OFICIO / LIBERTAD</h1>
          </div>

          <!-- panel blanco interno solo para contraste visual -->
          <div class=" rounded-3 p-2 mx-auto w-full">           
            <div class="d-flex flex-wrap gap-3 justify-content-center">
              <button type="button" class="button-Principal" onclick="selectForm('oficio')">OFICIO</button>
              <button type="button" class="button-Principal" onclick="selectForm('libertad')">LIBERTAD</button>
            </div>
          </div>
        </section>

        <!-- Cara POSTERIOR (formularios) -->
        <section class="face back text-start">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="flipBack()">← Volver</button>
            <div class="text-end">
              <span class="text-muted small">Módulo:</span>
              <strong id="flipTitle" class="ms-1">—</strong>
            </div>
          </div>

          <!-- Alertas -->
          <div id="alert-message-form1" class="alert alert-danger d-none mb-3" role="alert">
            <span id="alert-text-form1"></span>
            <button type="button" class="btn-close float-end" aria-label="Cerrar" onclick="closeAlert('form1')"></button>
          </div>
          <div id="alert-message-form2" class="alert alert-danger d-none mb-3" role="alert">
            <span id="alert-text-form2"></span>
            <button type="button" class="btn-close float-end" aria-label="Cerrar" onclick="closeAlert('form2')"></button>
          </div>

          <!-- ===== FORM OFICIO ===== -->
          <form id="form1"
                class="needs-validation d-none"
                action="{{ route('SistOficioLibertades.store') }}"
                method="POST"
                novalidate
                onsubmit="return validateForm(this, 'form1')">
            @csrf
            <input type="hidden" name="tipo" value="oficio">

            <h3 class="h5 fw-semibold mb-2">Solicitud para números de OFICIO</h3>
            <hr class="mt-0 mb-3">

            <div class="input-group input-group-lg mb-3">
              <span class="input-group-text">CAUSA ASIGNADA</span>
              <input type="text" class="form-control"
                     id="CausaAsigOf" name="CausaAsig"
                     required pattern="^[a-zA-Z0-9\s\-]{1,10}$" maxlength="10">
              <div class="invalid-feedback">Máx 10, letras/números/espacios/guion (-).</div>
            </div>

            <div class="mb-3">
              <select class="form-select form-select-lg"
                      id="UserSolicitanteOf" name="UserSolicitante" required>
                <option value="" disabled selected>SOLICITANTE</option>
                @foreach($solicitantes as $solicitante)
                  <option value="{{ $solicitante->nombre }}">{{ $solicitante->nombre }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback">Seleccione un solicitante válido.</div>
            </div>

            <div class="input-group input-group-lg mb-3">
              <span class="input-group-text">MOTIVO</span>
              <input type="text" class="form-control"
                     id="UserDirigidoOf" name="UserDirigido"
                     required pattern="^[a-zA-Z0-9\s\-]+$">
              <div class="invalid-feedback">Solo letras/números/espacios/guion (-).</div>
            </div>

            <div class="d-flex justify-content-center">
              <button type="submit" class="button-73">ENVIAR SOLICITUD</button>
            </div>
          </form>

          <!-- ===== FORM LIBERTAD ===== -->
          <form id="form2"
                class="needs-validation d-none"
                action="{{ route('SistOficioLibertades.store') }}"
                method="POST"
                novalidate
                onsubmit="return validateForm(this, 'form2')">
            @csrf
            <input type="hidden" name="tipo" value="libertad">

            <h3 class="h5 fw-semibold mb-2">Solicitud para números de LIBERTAD</h3>
            <hr class="mt-0 mb-3">

            <div class="input-group input-group-lg mb-3">
              <span class="input-group-text">CAUSA ASIGNADA</span>
              <input type="text" class="form-control"
                     id="CausaAsigLi" name="CausaAsig"
                     required pattern="^[a-zA-Z0-9\s\-]{1,10}$" maxlength="10">
              <div class="invalid-feedback">Máx 10, letras/números/espacios/guion (-).</div>
            </div>

            <div class="mb-3">
              <select class="form-select form-select-lg"
                      id="UserSolicitanteLi" name="UserSolicitante" required>
                <option value="" disabled selected>SOLICITANTE</option>
                @foreach($solicitantes as $solicitante)
                  <option value="{{ $solicitante->nombre }}">{{ $solicitante->nombre }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback">Seleccione un solicitante válido.</div>
            </div>

            <div class="input-group input-group-lg mb-3">
              <span class="input-group-text">MOTIVO</span>
              <input type="text" class="form-control"
                     id="UserDirigidoLi" name="UserDirigido"
                     required pattern="^[a-zA-Z0-9\s\-]+$">
              <div class="invalid-feedback">Solo letras/números/espacios/guion (-).</div>
            </div>

            <div class="d-flex justify-content-center">
              <button type="submit" class="button-73">ENVIAR SOLICITUD</button>
            </div>
          </form>
        </section>
      </div>
    </div>

    <!-- Modales Bootstrap (como los tenías) -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-confirm">
        <div class="modal-content">
          <div class="modal-header justify-content-center">
            <div class="icon-box"><i class="material-icons">&#xE876;</i></div>
            <h5 class="modal-title" id="successModalLabel">
              {{ session('tipo') === 'oficio' ? 'Oficio creado' : (session('tipo') === 'libertad' ? 'Libertad creada' : '') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body text-center">
            {{ session('success') }}
            <h4 class="mt-2">¡Solicitud exitosa!</h4>
            <p class="mb-1">Tu número de {{ session('tipo') === 'oficio' ? 'OFICIO' : 'LIBERTAD' }} es:</p>
            <h4 class="mb-3"><strong>{{ session('NumEntregado') }}-{{ session('año') }}</strong></h4>
            <a class="btn btn-success" href="{{ url('/SistOficioLibertades') }}">Volver al Inicio</a>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="errorModalLabel">Error</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">{{ session('error') }}</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

  </main>

  {{-- Bootstrap bundle (si no está en tu layout) --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
          crossorigin="anonymous"></script>

  <script>
    let currentForm = null; // 'oficio' | 'libertad'

    function selectForm(tipo){
      currentForm = tipo;

      // Título módulo
      document.getElementById('flipTitle').textContent = (tipo === 'oficio') ? 'OFICIO' : 'LIBERTAD';

      // Mostrar solo el formulario elegido
      document.getElementById('form1').classList.toggle('d-none', !(tipo === 'oficio'));
      document.getElementById('form2').classList.toggle('d-none', !(tipo === 'libertad'));

      // ✅ Voltear la TARJETA AZUL
      document.getElementById('blueCard').classList.add('flipped');

      // Limpiar alertas
      closeAlert('form1'); closeAlert('form2');
    }

    function flipBack(){
      document.getElementById('blueCard').classList.remove('flipped');
      currentForm = null;
    }

    function validateForm(form, formId){
      if (!form.checkValidity()) {
        const alertBox = document.getElementById(`alert-message-${formId}`);
        const alertTxt  = document.getElementById(`alert-text-${formId}`);
        if (alertBox && alertTxt) {
          alertTxt.textContent = 'Revisa los campos marcados en rojo. Respeta los formatos solicitados.';
          alertBox.classList.remove('d-none');
        }
        form.classList.add('was-validated');
        return false;
      }
      return true;
    }

    function closeAlert(formId){
      const alertBox = document.getElementById(`alert-message-${formId}`);
      if (alertBox) alertBox.classList.add('d-none');
    }

    // Mostrar modales según flash (si sigues usando Bootstrap)
    document.addEventListener('DOMContentLoaded', () => {
      const success = @json(session('success'));
      const error   = @json(session('error'));
      if (success) bootstrap.Modal.getOrCreateInstance('#successModal').show();
      if (error)   bootstrap.Modal.getOrCreateInstance('#errorModal').show();
    });
  </script>
</x-sist-layout>
