<?php

use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Red de seguridad: las propiedades públicas que representan estado de servidor
 * (modo, ids de registro, orden, selección, rol) deben ser #[Locked] para que el
 * navegador no pueda cambiarlas desde /livewire/update.
 *
 * Las excepciones son propiedades ligadas a campos de formulario o a la URL; se
 * validan o se normalizan con lista permitida en el servidor (ver cada componente).
 */
it('locks server-state properties of every livewire component', function () {
    $allowed = [
        'AudienciasDiariasCorreo::firmaId',          // <select> de firma, validado al enviar
        'BuscadorSentenciasTabla::sortField',        // ligado a la URL, lista permitida en render()
        'BuscadorSentenciasTabla::sortDirection',
        'InsumosGestionPage::directRecipientId',     // <select>, validado con exists
        'InsumosGestionPage::directInsumoId',
        'InsumosInventarioPage::reposicionSectorId',
        'LibertadTabla::sortField',                  // lista permitida en render()
        'LibertadTabla::sortDirection',
        'OficioTabla::sortField',
        'OficioTabla::sortDirection',
        'TtyppPage::selectedDate',                   // ligada a un input de fecha, normalizada con safeSelectedDate()
    ];

    $pattern = '/^(modo|publicMode|edit(ing)?Id|\w*Id|role|sortField|sortDirection|verEliminados|selected\w*)$/';
    $offenders = [];

    foreach (glob(app_path('Livewire/*.php')) as $file) {
        $class = 'App\\Livewire\\'.basename($file, '.php');

        if (! class_exists($class) || ! is_subclass_of($class, Component::class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() !== $class || ! preg_match($pattern, $property->getName())) {
                continue;
            }

            $key = class_basename($class).'::'.$property->getName();

            if ($property->getAttributes(Locked::class) === [] && ! in_array($key, $allowed, true)) {
                $offenders[] = $key;
            }
        }
    }

    expect($offenders)->toBe([], 'Propiedades de estado sin #[Locked] ni excepción justificada: '.implode(', ', $offenders));
});
