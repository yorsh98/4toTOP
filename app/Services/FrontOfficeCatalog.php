<?php

namespace App\Services;

/**
 * Accesos públicos de la portada. Una sola lista alimenta la portada,
 * el buscador (Ctrl K) y el menú móvil.
 */
class FrontOfficeCatalog
{
    /**
     * @return array<string, array{title: string, lead: string}>
     */
    public function sections(): array
    {
        return [
            'sistemas' => ['title' => 'Sistemas internos', 'lead' => 'Solicitudes y registros del tribunal.'],
            'audiencias' => ['title' => 'Audiencias', 'lead' => 'Agenda del día y estado de cada sala.'],
            'consultas' => ['title' => 'Consultas', 'lead' => 'Teléfonos, ausencias, sentencias y tribunales migrados.'],
            'herramientas' => ['title' => 'Herramientas', 'lead' => 'Utilidades de uso diario.'],
            'directorio' => ['title' => 'Directorio', 'lead' => 'Documentos, procedimientos y enlaces frecuentes.'],
        ];
    }

    /**
     * @return list<array{key: string, section: string, title: string, desc: string, href: ?string, icon: string, keywords: string, image?: string, drawer?: string, external?: bool}>
     */
    public function items(): array
    {
        return [
            ['key' => 'oficios', 'section' => 'sistemas', 'title' => 'Oficios y libertades', 'desc' => 'Pide un número de oficio o de libertad para una causa.', 'href' => route('SistOficioLibertades.index'), 'icon' => 'fa-file-signature', 'image' => 'img/S1.webp', 'keywords' => 'numero solicitud oficio libertad causa'],
            ['key' => 'audios', 'section' => 'sistemas', 'title' => 'Audios de salas', 'desc' => 'Audios de audiencias por sala y año.', 'href' => route('audios-salas.index'), 'icon' => 'fa-headphones', 'image' => 'img/S2.webp', 'keywords' => 'grabacion audio sala'],
            ['key' => 'cumpleanos', 'section' => 'sistemas', 'title' => 'Cumpleaños', 'desc' => 'Cumpleaños del mes del personal.', 'href' => 'http://10.13.214.129:8080/cumpleanos/', 'icon' => 'fa-cake-candles', 'image' => 'img/S3.webp', 'external' => true, 'keywords' => 'cumpleanos funcionarios'],

            ['key' => 'progra', 'section' => 'audiencias', 'title' => 'Programación diaria', 'desc' => 'Audiencias del día con sala, hora y causa.', 'href' => route('progra.index'), 'icon' => 'fa-calendar-days', 'keywords' => 'programacion agenda audiencias dia rit'],
            ['key' => 'estado', 'section' => 'audiencias', 'title' => 'Estado de audiencias', 'desc' => 'Revisa si cada audiencia de hoy está en curso, en receso o finalizada.', 'href' => null, 'drawer' => 'estado', 'icon' => 'fa-signal', 'keywords' => 'estado en curso receso finalizada'],
            ['key' => 'monitor', 'section' => 'audiencias', 'title' => 'Monitor', 'desc' => 'Pantalla de salas que se actualiza sola.', 'href' => route('Monitor'), 'icon' => 'fa-display', 'keywords' => 'monitor pantalla salas'],

            ['key' => 'guias', 'section' => 'consultas', 'title' => 'Guías telefónicas', 'desc' => 'Anexos y teléfonos de contacto.', 'href' => route('guiastelefonicas'), 'icon' => 'fa-phone', 'keywords' => 'telefono anexo guia contacto'],
            ['key' => 'ausencias', 'section' => 'consultas', 'title' => 'Ausencias', 'desc' => 'Quién no está hoy en el tribunal.', 'href' => route('ausencias'), 'icon' => 'fa-user-clock', 'keywords' => 'ausentismo licencia vacaciones'],
            ['key' => 'sentencias', 'section' => 'consultas', 'title' => 'Buscador de sentencias', 'desc' => 'Índice de sentencias del 4º TOP.', 'href' => route('buscadorsentencias'), 'icon' => 'fa-scale-balanced', 'keywords' => 'sentencia fallo indice buscar'],
            ['key' => 'unijud', 'section' => 'consultas', 'title' => 'Migración UNIJUD', 'desc' => 'Tribunales que ya migraron a UNIJUD.', 'href' => route('migraunijud'), 'icon' => 'fa-right-left', 'keywords' => 'unijud migracion tribunales'],

            ['key' => 'convertir', 'section' => 'herramientas', 'title' => 'Convertir archivos', 'desc' => 'PDF a Word y M4A a MP3.', 'href' => route('conversions.public'), 'icon' => 'fa-arrows-rotate', 'keywords' => 'pdf word docx m4a mp3 audio convertir'],
            ['key' => 'bitacora', 'section' => 'herramientas', 'title' => 'Bitácora de pruebas', 'desc' => 'Registro de pruebas por causa.', 'href' => route('bitacora-pruebas.public'), 'icon' => 'fa-clipboard-list', 'keywords' => 'bitacora pruebas evidencia'],
            ['key' => 'mapa', 'section' => 'herramientas', 'title' => 'Mapa de servicios', 'desc' => 'Plano público interactivo del tribunal.', 'href' => route('servicios.mapa-publico'), 'icon' => 'fa-map-location-dot', 'keywords' => 'mapa plano piso oficina servicios'],
            ['key' => 'ttypp', 'section' => 'herramientas', 'title' => 'TTyPP', 'desc' => 'Listado de testigos y peritos.', 'href' => route('ttypp.welcome'), 'icon' => 'fa-user-shield', 'keywords' => 'testigos peritos ttypp'],

            ['key' => 'doc', 'section' => 'directorio', 'title' => 'Documentos', 'desc' => 'Formularios y documentos para descargar.', 'href' => route('doc'), 'icon' => 'fa-folder-open', 'keywords' => 'documentos formularios descargar'],
            ['key' => 'proc', 'section' => 'directorio', 'title' => 'Procedimientos del tribunal', 'desc' => 'Procedimientos internos para consultar.', 'href' => route('proc'), 'icon' => 'fa-list-check', 'keywords' => 'procedimiento protocolo'],
            ['key' => 'acc', 'section' => 'directorio', 'title' => 'Accesos directos', 'desc' => 'Enlaces a sitios de uso frecuente.', 'href' => route('acc'), 'icon' => 'fa-link', 'keywords' => 'enlaces links accesos paginas'],
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function grouped(): array
    {
        $groups = array_fill_keys(array_keys($this->sections()), []);

        foreach ($this->items() as $item) {
            $groups[$item['section']][] = $item;
        }

        return $groups;
    }
}
