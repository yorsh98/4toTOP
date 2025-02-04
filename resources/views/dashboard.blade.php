<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inicio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("haz ingresado al sistema!") }}
                    <h1>Proximamente nuevas funcionalidades 😉</h1>
                    <hr><br>
                    <h2>Notas de la version:</h2>
                    <li>Apartado de Administracion de Oficio y Libertades deben actualizar dos veces la pestaña para un correcto funcionamiento</li>
                    <li>Seccion de buscar no realiza la busqueda por Numero entregado, pero si por los demas apartados</li>
                    <li>En el primer ingreso deben realizar el cambio de contraseña esto se realiza en la pestaña superior derecha al precionar su nombre posteriormente en Perfil</li>
                    <li>Cualquier sugerencia u error encontrado favor avisar al correo jtroncosor@pjud.cl</li>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
