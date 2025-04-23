
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud OFICIO/LIBERTADES </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto|Varela+Round">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <header class="banner">
        <h1 class="banner-title">4° Tribunal de Juicio Oral en lo Penal de Santiago</h1>
        <img src="/img/pjud_blanco.webp" alt="Descripción de la imagen">
    </header>
    <main class="container main-container" id="main-content">
        <h1 class="text-3xl font-bold mb-4">SOLICITUD PARA NUMEROS OFICIO / LIBERTAD</h1>
        <hr>
        <button class="button-Principal" onclick="showElement('form1')" role="button">OFICIO</button>
        <br><br>
        <button class="button-Principal" onclick="showElement('form2')" role="button">LIBERTAD</button>
    </main>
            <div id="form1" class="hidden rotateY-180-reverse">
                <main class="container main-container" id="main-content">
                    <h1 class="text-3xl font-bold mb-4">SOLICITUD PARA NUMEROS OFICIO</h1>
                    <hr>
                            <div id="alert-message-form1" class="alert alert-danger d-none" role="alert">
                                <span id="alert-text-form1"></span>
                                <button type="button" class="btn-close" aria-label="Cerrar" onclick="closeAlert('form1')"></button>
                            </div>

                            <form id="solicitudForm1" action="{{ route('SistOficioLibertades.store') }}" method="POST" class="form-container needs-validation" onsubmit="return validateForm(this, 'form1')">
                                @csrf
                                <input type="hidden" name="tipo" value="oficio">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text" id="inputGroup-sizing-lg">CAUSA ASIGNADA</span>
                                    <input type="text" class="form-control" id="CausaAsig" name="CausaAsig"  required pattern="^[a-zA-Z0-9\s\-]{1,10}$" maxlength="10">
                                </div><br>

                                <select class="form-select form-select-lg mb-3" id="UserSolicitante" name="UserSolicitante" required>
                                    <option value="" disabled selected>SOLICITANTE</option>
                                    @foreach($solicitantes as $solicitante)
                                        <option value="{{ $solicitante->nombre }}">{{ $solicitante->nombre }}</option>
                                    @endforeach
                                </select>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text" id="inputGroup-sizing-lg">MOTIVO</span>
                                    <input type="text" class="form-control" id="UserDirigido" name="UserDirigido" required pattern="^[a-zA-Z0-9\s\-]+$">
                                </div><hr>

                                <button type="submit" class="button-73">ENVIAR SOLICITUD</button>
                                <div id="alert-message" class="hidden">
                                    <p id="alert-text"></p>
                                    <button onclick="closeAlert()">Cerrar</button>
                                </div>
                            </form>


                </main>
            </div>

            <div id="form2" class="hidden rotateY-180">
                 <main class="container main-container" id="main-content">
                    <h1 class="text-3xl font-bold mb-4">SOLICITUD PARA NUMEROS DE LIBERTAD</h1>
                    <hr>
                    <div id="alert-message-form2" class="alert alert-danger d-none" role="alert">
                                <span id="alert-text-form2"></span>
                                <button type="button" class="btn-close" aria-label="Cerrar" onclick="closeAlert('form2')"></button>
                            </div>

                            <form id="solicitudForm2" action="{{ route('SistOficioLibertades.store') }}" method="POST" class="form-container needs-validation" onsubmit="return validateForm(this, 'form2')">
                                @csrf
                                <input type="hidden" name="tipo" value="libertad">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text" id="inputGroup-sizing-lg">CAUSA ASIGNADA</span>
                                    <input type="text" class="form-control" id="CausaAsig" name="CausaAsig" required pattern="^[a-zA-Z0-9\s\-]{1,10}$" maxlength="10">
                                </div><br>

                                <select class="form-select form-select-lg mb-3" id="UserSolicitante" name="UserSolicitante" required>
                                <option value="" disabled selected>SOLICITANTE</option>
                                    @foreach($solicitantes as $solicitante)
                                        <option value="{{ $solicitante->nombre }}">{{ $solicitante->nombre }}</option>
                                    @endforeach
                                </select>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text" id="inputGroup-sizing-lg">MOTIVO</span>
                                    <input type="text" class="form-control" id="UserDirigido" name="UserDirigido" required pattern="^[a-zA-Z0-9\s\-]+$">
                                </div><hr>

                                <button type="submit" class="button-73">ENVIAR SOLICITUD</button>
                                <div id="alert-message" class="hidden">
                                    <p id="alert-text"></p>
                                    <button onclick="closeAlert()">Cerrar</button>
                                </div>
                            </form>
                </main> 
            
             </div>

        <div class="text-center">
</div>

<!-- Modal HTML -->
<!-- Modal de éxito -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-confirm"">
    <div class="modal-content">
      <div class="modal-header justify-content-center">
                <div class="icon-box">
					<i class="material-icons">&#xE876;</i>
				</div>
        <h5 class="modal-title" id="successModalLabel"></h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        {{ session('success') }}
				<h4>Solicitud exitosa!</h4>	
				<p>Tu numero de OFICIO / LIBERTAD es: </p>
                <h4><strong>{{ session('NumEntregado') }}-{{ session('año') }}</strong></h4>
				<a class="btn btn-success" href="{{ url('/SistOficioLibertades') }}" data-dismiss="modal"><span>Volver al Inicio</span> <i class="material-icons">&#xE5C8;</i></a>                
			</div>
      </div>
      <div class="modal-footer">
        
      </div>
    </div>
  </div>
</div>

<!-- Modal de error -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="errorModalLabel">Error</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        {{ session('error') }}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
    <x-footer-sol />
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <script src="/js/app.js"></script>
    
    <script>

function solicitarOtroNumero() {
        
    $(document).on('click', '[data-dismiss="modal"]', function() {
    $('#successModal').modal('hide');
});

    
        // Cerrar el modal
        $('successModal').modal('hide');  // Reemplaza 'myModal' con el ID real de tu modal
       
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        // Comprobar si existen mensajes de éxito o error desde PHP con valores en atributos data
        var successMessage = "{{ session('success') }}";
        var errorMessage = "{{ session('error') }}";

        if (successMessage) {
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        }

        if (errorMessage) {
            var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
        }
    });

    // Bootstrap 5 validation
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()


    
    /*
    document.getElementById('solicitudForm').addEventListener('submit', async function (e) {
    e.preventDefault(); // Evita el envío tradicional del formulario

    let formData = new FormData(this);

    try {
        let response = await fetch(this.action, {
            method: 'POST',
            body: formData,
        });

        let result = await response.json();

        if (result.success) {
            alert('Solicitud enviada con éxito');
            console.log('Datos recibidos:', result.data);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error al enviar la solicitud');
        console.error('Error:', error);
    }
});  */

//axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

</script>

    
</body>
</html>




