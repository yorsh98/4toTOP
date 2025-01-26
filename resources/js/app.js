import './bootstrap';

import axios from 'axios';

axios.defaults.baseURL = process.env.MIX_API_BASE_URL || 'http://127.0.0.1:8001/api';
axios.defaults.headers.common['Accept'] = 'application/json';


// envio datos desde el form hacia la API no funciona lo veo desde el js de public

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


document.getElementById('Form1').addEventListener('submit', async function (e) {
    e.preventDefault();  // Evita el envío tradicional del formulario

    // Obtener valores del formulario
    const causa = document.getElementById('causa').value;
    const solicitante = document.getElementById('solicitante').value;
    const motivo = document.getElementById('motivo').value;

    // Validación básica
    if (!causa || !solicitante || !motivo) {
        alert('Por favor, completa todos los campos');
        return;
    }

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
});

