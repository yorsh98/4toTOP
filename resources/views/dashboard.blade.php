<x-app-layout>
    <x-insertbootstrap />
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inicio') }}
        </h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <h1>Bienvenido al Backoffice de la web interna del 4to TOP</h1>
                    <hr><br>
                    <h1>Proximamente nuevas funcionalidades 😉</h1>
                    <h2>Notas de la version:</h2>
                    <li>Apartado de Administracion de Oficio/Libertades/Guias deben actualizar dos veces la pestaña para un correcto funcionamiento</li>
                    <li>Seccion de buscar no realiza la busqueda por Numero entregado, pero si por los demas apartados</li>
                    <li>Seccion Funcionarios funciona directamente con sistema de oficios y libertades</li>
                    <li>Seccion turnos se debe actualizar cada semana para desplegar la informacion para los funcionarios</li>                   
                    <li>Cualquier sugerencia u error encontrado favor avisar al correo jtroncosor@pjud.cl</li>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
