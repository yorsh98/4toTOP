<?php

use App\NotificationsV2\Application\Services\SimpleTemplateRenderer;
use App\NotificationsV2\Application\Services\TurnoOneWeeklySummaryNotifier;
use App\NotificationsV2\Application\Services\TurnoTwoAcdWeeklySummaryNotifier;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Support\OutlookEmailTemplates;
use Illuminate\Support\Carbon;

test('renders uniform Outlook data cells in the official turno templates', function (string $method, bool $assigned) {
    $person = [
        'run' => '12.345.678-5',
        'nombre' => 'Elena & Diego',
        'telefono' => '222222222',
        'correo' => 'elena@example.cl',
        'cargo' => 'Equipo <1>',
    ];
    $date = Carbon::parse('2026-05-18');
    $week = '18-05-2026 al 22-05-2026';
    $people = $assigned ? [$person] : [];
    $turnoOne = $method === 'turno1ResumenSemanalHtml';
    $tribunal = 'Tribunal & Sala <Norte>';
    config(['notifications_v2.turno1_summary.tribunal_oral' => $tribunal]);
    $payload = $turnoOne
        ? app(TurnoOneWeeklySummaryNotifier::class)->buildPayload($date, $week, $person, $person, $people)
        : app(TurnoTwoAcdWeeklySummaryNotifier::class)->buildPayload($date, $week, $tribunal, $people, $person, $people);
    $renderer = new SimpleTemplateRenderer;
    $render = fn (string $html): string => $renderer->render(new NotificationTemplate([
        'subject' => '{{ semana_rango }}',
        'body_html' => $html,
    ]), $payload)['html'];

    $outlook = $render(OutlookEmailTemplates::$method());
    expect($outlook)->not->toContain('{{');

    $dom = new DOMDocument;
    $dom->loadHTML($outlook, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    $cells = $xpath->query('//table[not(@role)]/tr/td');
    $expectedCount = $turnoOne ? 11 : ($assigned ? 11 : 21);
    expect($cells->length)->toBe($expectedCount + ($assigned ? 5 : 1));
    $tribunalCells = $xpath->query('//table[not(@role)]/tr/td[@rowspan]');
    expect($tribunalCells->length)->toBe(1);
    $tribunalCell = $tribunalCells->item(0);
    $coveredRows = (int) $xpath->evaluate('1 + count(../following-sibling::tr)', $tribunalCell);
    expect((int) $tribunalCell->getAttribute('rowspan'))->toBe($coveredRows);

    foreach ($cells as $cell) {
        expect($cell->getAttribute('bgcolor'))->not->toBeEmpty();
        expect($cell->getAttribute('style'))
            ->toContain('font-family:Arial,Helvetica,sans-serif;', 'mso-line-height-rule:exactly;', 'font-weight:normal;', 'color:#1F2937;');

        foreach ([$cell, ...$xpath->query('.//*[@style]', $cell)] as $element) {
            preg_match_all('/(?:^|;)\s*font-size:\s*([^;]+)/i', $element->getAttribute('style'), $sizes);
            preg_match_all('/(?:^|;)\s*line-height:\s*([^;]+)/i', $element->getAttribute('style'), $heights);
            expect($sizes[1])->toBe(['12px']);
            expect($heights[1])->toBe(['16px']);
        }
    }

    if ($assigned) {
        expect($outlook)->toContain('Elena &amp; Diego', 'Equipo &lt;1&gt;', 'mailto:elena@example.cl');
    } else {
        expect($xpath->query('//table[not(@role)]/tr/td[@colspan="5" and text()="Sin funcionarios asignados."]')->length)->toBe(1);
    }

    $code = $turnoOne ? 'turno1.resumen_semanal' : 'turno2.acd.resumen_semanal';
    $template = new NotificationTemplate(OutlookEmailTemplates::forType($code));
    $olderPayload = array_diff_key($payload, ['tribunal_oral_html' => true, 'semana_rango_html' => true]);
    $fromQueue = $renderer->render($template, $olderPayload);

    expect($fromQueue['html'])->not->toContain('{{')->toContain(e($tribunal))
        ->and($fromQueue['text'])->toContain($tribunal)
        ->and(substr_count($outlook, e($tribunal)))->toBe(2)
        ->and($outlook)->not->toContain($tribunal)
        ->and($payload['tribunal_oral'])->toBe($tribunal);
})->with([
    'Turno 1 with funcionarios' => ['turno1ResumenSemanalHtml', true],
    'Turno 1 without funcionarios' => ['turno1ResumenSemanalHtml', false],
    'Turno 2 with magistrados and funcionarios' => ['turno2AcdResumenSemanalHtml', true],
    'Turno 2 without magistrados or funcionarios' => ['turno2AcdResumenSemanalHtml', false],
]);
