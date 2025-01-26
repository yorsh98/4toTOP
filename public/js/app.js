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
/*function validateForm(form) {
    var inputs = form.querySelectorAll('input');
    var selects = form.querySelectorAll('select');
    var regex = /^[a-zA-Z0-9-]+$/;

    for (var input of inputs) {
        if (!regex.test(input.value)) {
            alert('Por favor, ingresa solo letras, números y el símbolo "-" en los campos.');
            return false;
        }
    }

    for (var select of selects) {
        if (!regex.test(select.value)) {
            alert('Por favor, ingresa solo letras, números y el símbolo "-" en los campos.');
            return false;
        }
    }

    return true;
}

function handleFormSubmit(event) {
    event.preventDefault(); // Evita el envío del formulario normal
    if (validateForm(event.target)) {
        $('#myModal').modal('show'); // Muestra el modal solo si la validación es exitosa
    }
}

document.getElementById('form1').addEventListener('submit', handleFormSubmit);
document.getElementById('form2').addEventListener('submit', handleFormSubmit);

// Asegúrate de que el botón dentro del modal cierre el modal correctamente
$(document).on('click', '[data-dismiss="modal"]', function() {
    $('#myModal').modal('hide');
});

// Función para volver al inicio de la página
document.getElementById('backToTop').addEventListener('click', function() {
    $('#myModal').modal('hide'); // Cierra el modal
    window.scrollTo({ top: 0, behavior: 'smooth' }); // Desplaza al inicio de la página
});*/

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

    for (var select of selects) {
        var selectedOption = select.options[select.selectedIndex];
        if (!regex.test(selectedOption.value) || selectedOption.value === "") {
            alert('Por favor, selecciona una opción válida.');
            return false;
        }
    }

    return true;
}

function handleFormSubmit(event) {
    event.preventDefault(); // Evita el envío del formulario normal
    if (validateForm(event.target)) {
        $('#myModal').modal('show'); // Muestra el modal solo si la validación es exitosa
    }
}

document.getElementById('form1').addEventListener('submit', handleFormSubmit);
document.getElementById('form2').addEventListener('submit', handleFormSubmit);

// Asegúrate de que el botón dentro del modal cierre el modal correctamente
$(document).on('click', '[data-dismiss="modal"]', function() {
    $('#myModal').modal('hide');
});

// envio datos desde el form hacia la API no funciono 
/*
async function enviarFormulario(form1) {
    try {
        const response = await axios.post('http://127.0.0.1:8001/api/Oficio', {
            ingreso1: datos.ingreso1,
            ingreso2: datos.ingreso2,
            ingreso3: datos.ingreso3,
        });

        // Manejar la respuesta de la API
        console.log('Respuesta API:', response.data);
        mostrarModal(response.data);  // Llamar función para mostrar modal con los datos
    } catch (error) {
        console.error('Error al enviar datos:', error.response.data);
    }
}


document.getElementById('solicitudForm').addEventListener('submit', async function (e) {
    e.preventDefault();  // Evita el envío tradicional del formulario

    // Obtener valores del formulario
    const causa = document.getElementById('causa').value;
    const solicitante = document.getElementById('solicitante').value;
    const motivo = document.getElementById('motivo').value;



    // Crear objeto con los datos
    const formData = {
        causa: causa,
        solicitante: solicitante,
        motivo: motivo
    };

    try {
        // Enviar datos a la API usando Axios
        const response = await axios.post('http://127.0.0.1:8001/api/Oficio', formData);

        // Manejar la respuesta exitosa
        console.log('Respuesta de la API:', response.data);
        alert('Solicitud enviada con éxito');

        // Aquí puedes mostrar los datos en un modal si es necesario
    } catch (error) {
        console.error('Error en la solicitud:', error.response?.data || error);
        alert('Hubo un problema al enviar la solicitud');
    }
});*/

