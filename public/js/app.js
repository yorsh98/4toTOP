
//HABILITACION DE LOS FORMULARIOS AL PRECIONAR LOS BOTONES.
function showElement(elementId) {
    const mainContent = document.getElementById('main-content');
    const element = document.getElementById(elementId);

    mainContent.classList.add('hidden');
    mainContent.classList.remove('visible');

    element.classList.remove('hidden');
    element.classList.add('show');

    setTimeout(() => {
        element.classList.add('visible');
        element.classList.remove('show');
    }, 1); // Duración de la animación
}

function goBack() {
    const mainContent = document.getElementById('main-content');
    const form1 = document.getElementById('form1');
    const form2 = document.getElementById('form2');

    mainContent.classList.add('visible');
    mainContent.classList.remove('hidden');

    form1.classList.add('hidden');
    form1.classList.remove('visible');
    form1.classList.remove('show');

    form2.classList.add('hidden');
    form2.classList.remove('visible');
    form2.classList.remove('show');
}

//VALIDACION DEL FORMULARIO Y ARRANQUE DEL MODAL
function validateForm(form) {
    var inputs = form.querySelectorAll('input');
    var selects = form.querySelectorAll('select');
    var regex = /^[a-zA-Z0-9-]+$/;
    var isValid = true;

    for (var input of inputs) {
        if (!regex.test(input.value.trim()) || input.value.trim() === '') {
            showAlert(`El campo "${input.name}" contiene caracteres no válidos o está vacío.`);
            input.classList.add('input-error');
            isValid = false;
        } else {
            input.classList.remove('input-error');
        }
    }

    for (var select of selects) {
        if (!regex.test(select.value.trim()) || select.value.trim() === '') {
            showAlert(`El campo "${select.name}" contiene caracteres no válidos o está vacío.`);
            select.classList.add('input-error');
            isValid = false;
        } else {
            select.classList.remove('input-error');
        }
    }

    return isValid;
}

// Función para mostrar una alerta visual en la página
function showAlert(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert-box';
    alertDiv.innerText = message;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 4000);
}

// CSS para resaltar campos con error y mostrar la alerta
const style = document.createElement('style');
style.innerHTML = `
    .input-error {
        border: 2px solid red;
        background-color: #fdd;
    }
    .alert-box {
        position: fixed;
        top: 20px;
        right: 20px;
        background: red;
        color: white;
        padding: 15px;
        border-radius: 5px;
        font-weight: bold;
        z-index: 1000;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
`;
document.head.appendChild(style);

function handleFormSubmit(event) {
    //event.preventDefault(); // Evita el envío del formulario normal
    if (validateForm(event.target)) {
        $('#myModal').modal('show'); // Muestra el modal solo si la validación es exitosa
    }
}

document.getElementById('form1').addEventListener('submit', handleFormSubmit);
document.getElementById('form2').addEventListener('submit', handleFormSubmit);

// Asegúrate de que el botón dentro del modal cierre el modal correctamente
$(document).on('click', '[data-dismiss="modal"]', function() {
    $('#successModal').modal('hide');
});

// Función para volver al inicio de la página
document.getElementById('backToTop').addEventListener('click', function() {
    $('#myModal').modal('hide'); // Cierra el modal
    window.scrollTo({ top: 0, behavior: 'smooth' }); // Desplaza al inicio de la página
});
/*
function validateForm(form) {
    var inputs = form.querySelectorAll('input');
    var selects = form.querySelectorAll('select');
    var regex = /^[a-zA-Z0-9-]+$/;

    for (var input of inputs) {
        if (!regex.test(input.value)) {
            alert('Por favor, ingresa solo letras, números y el símbolo "-" en los campos.');
            return false;
        }
    }

    

    return true;
}

/*function handleFormSubmit(event) {
    //event.preventDefault(); // Evita el envío del formulario normal
    if (validateForm(event.target)) {
        $('#myModal').modal('show'); // Muestra el modal solo si la validación es exitosa
    }
}

document.getElementById('form1').addEventListener('submit', handleFormSubmit);
document.getElementById('form2').addEventListener('submit', handleFormSubmit);

// Asegúrate de que el botón dentro del modal cierre el modal correctamente
$(document).on('click', '[data-dismiss="modal"]', function() {
    $('#myModal').modal('hide');
});*/

function showElement(elementId) {
    const mainContent = document.getElementById('main-content');
    const element = document.getElementById(elementId);

    mainContent.classList.add('hidden');
    mainContent.classList.remove('visible');

    element.classList.remove('hidden');
    element.classList.add('show');

    setTimeout(() => {
        element.classList.add('visible');
        element.classList.remove('show');
    }, 1);
}

function goBack() {
    const mainContent = document.getElementById('main-content');
    const form1 = document.getElementById('form1');
    const form2 = document.getElementById('form2');

    mainContent.classList.add('visible');
    mainContent.classList.remove('hidden');

    form1.classList.add('hidden');
    form1.classList.remove('visible');
    form1.classList.remove('show');

    form2.classList.add('hidden');
    form2.classList.remove('visible');
    form2.classList.remove('show');
}

function validateForm(form) {
    var inputs = form.querySelectorAll('input');
    var selects = form.querySelectorAll('select');
    var regex = /^[a-zA-Z0-9\s-]+$/;  // Permite letras, números, espacios y guiones

    for (var input of inputs) {
        if (!regex.test(input.value.trim())) {
            showAlert('Por favor, ingresa solo letras, números, espacios y el símbolo "-" en los campos.');
            return false;
        }
    }

    for (var select of selects) {
        if (!regex.test(select.value.trim())) {
            showAlert('Por favor, selecciona una opción válida.');
            return false;
        }
    }

    return true;
}


function showAlert(message) {
    var alertBox = document.getElementById('alert-message');
    document.getElementById('alert-text').textContent = message;
    alertBox.classList.remove('d-none');
}

// Función para cerrar la alerta
function closeAlert() {
    document.getElementById('alert-message').classList.add('d-none');
}

// Validación personalizada del formulario
(function () {
    'use strict';
    var form = document.getElementById('myForm');

    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            showAlert('Por favor, corrige los errores en el formulario antes de enviarlo.');
        }
        form.classList.add('was-validated');
    }, false);
    })();


/*
function showAlert(message) {
    var alertBox = document.getElementById('alert-message');
    document.getElementById('alert-text').textContent = message;
    alertBox.classList.remove('hidden');
}

function closeAlert() {
    document.getElementById('alert-message').classList.add('hidden');
}*/

