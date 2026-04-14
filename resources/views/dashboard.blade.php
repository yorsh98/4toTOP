<x-app-layout>
   
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inicio') }}
        </h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="mb-3 text-2xl font-semibold text-gray-900">Bienvenido(a) al Backoffice del 4to TOP</h1>

                    <p class="mb-4 text-sm leading-6 text-gray-700">
                        Este sistema centraliza la gestion operativa interna del tribunal en un solo lugar,
                        con accesos y acciones controladas segun perfil de usuario.
                    </p>

                    <h2 class="mb-2 text-lg font-semibold text-gray-900">Modulos disponibles</h2>
                    <ul class="mb-5 list-disc space-y-1 pl-6 text-sm text-gray-700">
                        <li><strong>Funcionarios:</strong> catalogo global utilizado por Turnos, Audiencias y Oficios/Libertades.</li>
                        <li><strong>Oficios / Libertades:</strong> administracion de registros, busqueda, filtros por año, edicion y restauracion.</li>
                        <li><strong>Turnos:</strong> gestion de turnos semanales y asignaciones por rol.</li>
                        <li><strong>Guias:</strong> administracion de guias telefonicas con busqueda, filtros y mantenimiento de datos.</li>
                        <li><strong>Audiencias:</strong> carga y administracion diaria, exportacion de informacion y envio por correo.</li>
                        <li><strong>Ausencias:</strong> registro y seguimiento de ausentismo del personal.</li>
                        <li><strong>Seguridad:</strong> administracion de roles, permisos, usuarios y auditoria de acciones.</li>
                    </ul>

                    <h2 class="mb-2 text-lg font-semibold text-gray-900">Recomendaciones de uso</h2>
                    <ul class="list-disc space-y-1 pl-6 text-sm text-gray-700">
                        <li>Mantener actualizado el catalogo de Funcionarios para asegurar consistencia entre modulos.</li>
                        <li>Verificar permisos y roles antes de habilitar nuevas acciones.</li>
                        <li>Registrar incidencias o sugerencias al correo de soporte: <a href="mailto:jtroncosor@pjud.cl" class="font-medium text-sky-700 hover:text-sky-800">jtroncosor@pjud.cl</a>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
