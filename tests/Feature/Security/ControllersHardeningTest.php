<?php

use App\Models\Funcionario;
use App\Models\User;
use App\Support\SafeSpreadsheetValueBinder;
use Database\Seeders\RolesAndPermissionsSeeder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('keeps the public correlative request available for a legitimate submission', function () {
    Funcionario::create([
        'nombre' => 'Jefatura Permitida', 'rol' => 'jefatura',
        'email' => 'jefatura-hardening@example.cl', 'fecha_cumpleanos' => '1982-03-03',
    ]);
    $data = ['tipo' => 'oficio', 'CausaAsig' => 'B1-2026', 'UserSolicitante' => 'Jefatura Permitida', 'UserDirigido' => 'Prueba motivo'];

    $this->post(route('SistOficioLibertades.store'), $data)->assertSessionHas('success');
});

it('rejects a motivo longer than the database column', function () {
    Funcionario::create([
        'nombre' => 'Jefatura Permitida', 'rol' => 'jefatura',
        'email' => 'jefatura-long@example.cl', 'fecha_cumpleanos' => '1982-03-03',
    ]);
    $this->post(route('SistOficioLibertades.store'), [
        'tipo' => 'oficio', 'CausaAsig' => 'B1-2026',
        'UserSolicitante' => 'Jefatura Permitida', 'UserDirigido' => str_repeat('a', 256),
    ])->assertSessionHasErrors('UserDirigido');
});

it('validates export dates and search length while accepting normal filters', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $this->actingAs($user)->get(route('ttypp.export', ['fecha' => '2026-02-30']))->assertSessionHasErrors('fecha');
    $this->get(route('ttypp.export', ['fecha' => '2026-02-01', 'search' => str_repeat('a', 101)]))->assertSessionHasErrors('search');
    $this->get(route('ttypp.export', ['fecha' => '2026-02-01', 'search' => 'causa']))->assertOk();
});

it('exports formula-like text as text while preserving ordinary values', function () {
    $spreadsheet = new Spreadsheet;
    $spreadsheet->setValueBinder(new SafeSpreadsheetValueBinder);
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(['=1+1', 'Texto normal', 42]);

    expect($sheet->getCell('A1')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($sheet->getCell('A1')->getValue())->toBe('=1+1')
        ->and($sheet->getCell('B1')->getValue())->toBe('Texto normal')
        ->and($sheet->getCell('C1')->getValue())->toBe(42);
});
