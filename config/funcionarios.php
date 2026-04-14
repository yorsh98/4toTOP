<?php

return [
    'roles' => [
        'juez' => 'Juez',
        'sala_causa' => 'Sala/Causa',
        'ttypp' => 'TTyPP',
        'encargado_causa' => 'Encargado causa',
        'jefatura' => 'Jefatura',
        'informatico' => 'Informatico',
    ],

    'contexts' => [
        'audiencias.jueces' => ['juez'],
        'audiencias.acta_anfitrion' => ['sala_causa'],
        'audiencias.ttp' => ['ttypp'],
        'audiencias.encargado_causa' => ['encargado_causa'],
        'turnos.magistrados' => ['juez'],
        'turnos.funcionarios' => ['sala_causa', 'ttypp', 'informatico'],
        'turnos.jefatura' => ['encargado_causa', 'jefatura'],
        'oficios.solicitantes' => ['sala_causa', 'ttypp', 'encargado_causa', 'jefatura', 'informatico'],
    ],
];
