<?php

test('guest can download the daily hearings spreadsheet', function () {
    $this->get(route('audiencias.export.diaria', ['fecha' => '2026-08-06']))
        ->assertDownload('Programacion_4TOP_2026-08-06.xlsx');
});
