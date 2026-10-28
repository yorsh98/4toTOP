<?php

namespace App\Services;

class BackofficeModules
{
    public function items(): array
    {
        return [
            ['label' => __('Funcionarios'), 'name' => 'Funcionarios', 'description' => 'Administra el catálogo de funcionarios.', 'route' => 'addfuncionario.index', 'active' => 'addfuncionario*', 'icon' => 'fa-solid fa-users', 'permissions' => ['navbar.funcionarios.visible', 'navbar.funcionarios.editable'], 'accent' => '#0284c7'],
            ['label' => __('Oficios / Libertades'), 'name' => 'Oficios / Libertades', 'description' => 'Gestiona solicitudes, oficios y libertades.', 'route' => 'oficiolibertad', 'active' => 'oficiolibertad', 'icon' => 'fa-solid fa-file-signature', 'permissions' => ['navbar.oficios_libertades.visible', 'navbar.oficios_libertades.editable'], 'accent' => '#2563eb'],
            ['label' => __('Turnos'), 'name' => 'Turnos', 'description' => 'Consulta y asigna turnos semanales.', 'route' => 'turno', 'active' => 'turno', 'icon' => 'fa-solid fa-calendar-days', 'permissions' => ['navbar.turnos.visible', 'navbar.turnos.editable'], 'accent' => '#4f46e5'],
            ['label' => __('Guias'), 'name' => 'Guías', 'description' => 'Mantén actualizada la guía telefónica.', 'route' => 'guias', 'active' => 'guias', 'icon' => 'fa-solid fa-book', 'permissions' => ['navbar.guias.visible', 'navbar.guias.editable'], 'accent' => '#0891b2'],
            ['label' => __('Audiencias'), 'name' => 'Audiencias', 'description' => 'Organiza la programación y gestión diaria.', 'route' => 'AdmAudv', 'active' => 'AdmAudv', 'icon' => 'fa-solid fa-gavel', 'permissions' => ['navbar.audiencias.visible', 'navbar.audiencias.editable'], 'accent' => '#7c3aed'],
            ['label' => 'TTYPP', 'name' => 'TTYPP', 'description' => 'Registra y consulta causas TTYPP.', 'route' => 'ttypp.index', 'active' => 'ttypp.*', 'icon' => 'fa-solid fa-user-shield', 'permissions' => ['navbar.ttypp.visible', 'navbar.ttypp.editable'], 'accent' => '#c026d3'],
            ['label' => __('Bitacora Pruebas'), 'name' => 'Bitácora de Pruebas', 'description' => 'Documenta y revisa controles operativos.', 'route' => 'bitacora-pruebas.index', 'active' => 'bitacora-pruebas.*', 'icon' => 'fa-solid fa-clipboard-list', 'permissions' => ['navbar.bitacora_pruebas.visible', 'navbar.bitacora_pruebas.editable'], 'accent' => '#059669'],
            ['label' => __('Ausencias'), 'name' => 'Ausencias', 'description' => 'Registra y consulta ausentismo del personal.', 'route' => 'ausentismo', 'active' => 'ausentismo', 'icon' => 'fa-solid fa-user-clock', 'permissions' => ['navbar.ausencias.visible', 'navbar.ausencias.editable'], 'accent' => '#d97706'],
            ['label' => __('Documentos'), 'name' => 'Documentos', 'description' => 'Gestiona documentos y procedimientos.', 'route' => 'backoffice.documentos.index', 'active' => 'backoffice.documentos.*', 'icon' => 'fa-solid fa-folder-open', 'permissions' => ['navbar.documentos.visible', 'navbar.documentos.editable'], 'accent' => '#ea580c'],
            ['label' => 'Convertir archivos', 'name' => 'Convertir archivos', 'description' => 'Convierte PDF a Word y M4A a MP3.', 'route' => 'conversions.backoffice', 'active' => 'conversions.*', 'icon' => 'fa-solid fa-arrow-right-arrow-left', 'permissions' => ['navbar.conversiones.visible'], 'accent' => '#0284c7'],
            ['label' => __('Audios de Salas'), 'name' => 'Audios de Salas', 'description' => 'Consulta y administra audios de salas.', 'route' => 'audios-salas.index', 'active' => 'audios-salas.*', 'icon' => 'fa-solid fa-headphones', 'permissions' => ['audios_salas.ver'], 'accent' => '#e11d48'],
            ['label' => __('Visita Carcel'), 'name' => 'Visita Carcel', 'description' => 'Centraliza la gestion de visitas a recinto penitenciario.', 'route' => 'visita-carcel.index', 'active' => 'visita-carcel.*', 'icon' => 'fa-solid fa-building-shield', 'permissions' => ['navbar.visita_carcel.visible', 'visita_carcel.ver'], 'accent' => '#be123c'],
            ['label' => __('Servicios'), 'name' => 'Servicios', 'description' => 'Administra el inventario de servicios.', 'route' => 'backoffice.servicios.inventario', 'active' => 'backoffice.servicios.inventario', 'icon' => 'fa-solid fa-boxes-stacked', 'permissions' => ['servicios.ver'], 'accent' => '#0d9488'],
            ['label' => __('Mapa Servicios'), 'name' => 'Mapa de Servicios', 'description' => 'Explora la cobertura de servicios.', 'route' => 'backoffice.servicios.mapa', 'active' => 'backoffice.servicios.mapa', 'icon' => 'fa-solid fa-map-location-dot', 'permissions' => ['servicios.ver'], 'accent' => '#65a30d'],
            ['label' => __('Insumos'), 'name' => 'Insumos', 'description' => 'Solicita y consulta los insumos disponibles.', 'route' => 'insumos.solicitudes', 'active' => 'insumos.*', 'icon' => 'fa-solid fa-box-open', 'permissions' => ['navbar.insumos.visible', 'navbar.insumos.editable', 'insumos.solicitar'], 'accent' => '#0891b2'],
            ['label' => __('Seguridad'), 'name' => 'Seguridad', 'description' => 'Administra roles, usuarios y auditoría.', 'route' => 'seguridad.roles.index', 'active' => 'seguridad.*', 'icon' => 'fa-solid fa-shield-halved', 'permissions' => ['navbar.seguridad.visible', 'navbar.seguridad.editable'], 'accent' => '#94a3b8'],
        ];
    }
}
