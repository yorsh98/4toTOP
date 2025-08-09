<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test SweetAlert2</title>
    @vite(['resources/css/app.css', 'resources/js/app.js']) {{-- Asegúrate que esté usando Vite --}}
</head>
<body class="p-10">

    <button id="btn-alerta" class="bg-blue-600 text-white px-4 py-2 rounded shadow">
        Mostrar Alerta
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const boton = document.getElementById('btn-alerta');
            boton.addEventListener('click', function () {
                Swal.fire({
                    title: '¡Funciona!',
                    text: 'SweetAlert2 está bien integrado.',
                    icon: 'success',
                    confirmButtonText: 'Ok'
                });
            });
        });
    </script>

</body>
</html>
