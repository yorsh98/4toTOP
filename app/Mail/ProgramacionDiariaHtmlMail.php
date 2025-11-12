<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue; // si luego usarás colas
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class ProgramacionDiariaHtmlMail extends Mailable // implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $fecha;
    public array $juicios;
    public array $lecturas;
    public array $cortas;
    public array $juecesAusentes;
    public ?string $turno1;
    public ?string $turno2;
    public ?string $turno3;
    public string $firmaHtml;

    public function __construct(
        string $fecha,
        array $juicios,
        array $lecturas,
        array $cortas,
        array $juecesAusentes,
        ?string $turno1 = null,
        ?string $turno2 = null,
        ?string $turno3 = null,
        string $firmaHtml = ''
    ) {
        $this->fecha         = $fecha;
        $this->juicios       = $juicios;
        $this->lecturas      = $lecturas;
        $this->cortas        = $cortas;
        $this->juecesAusentes= $juecesAusentes;
        $this->turno1        = $turno1;
        $this->turno2        = $turno2;
        $this->turno3        = $turno3;
        $this->firmaHtml     = $firmaHtml;
    }

    // OJO: no uses envelope()/content() si vas a usar build()+html() manual.
    // (Puedes borrarlos para evitar duplicidad.)

    public function build()
    {
        // 1) Renderiza Blade a HTML (incluye la firma en la vista)
        $html = view('emails.programacion-diaria-html', [
            'fechaHuman'     => Carbon::parse($this->fecha,'America/Santiago')->isoFormat('dddd D [de] MMMM YYYY'),
            'juicios'        => $this->juicios,
            'lecturas'       => $this->lecturas,
            'cortas'         => $this->cortas,
            'juecesAusentes' => $this->juecesAusentes,
            'turno1'         => $this->turno1,
            'turno2'         => $this->turno2,
            'turno3'         => $this->turno3,
            'firmaHtml'      => $this->firmaHtml,
        ])->render();

        // 2) Inlinar CSS (mejor compatibilidad con Outlook)
        $inliner = new CssToInlineStyles();
        $inlined = $inliner->convert($html);

        // 3) Asunto (no seteamos FROM aquí para no pisar el dinámico del controlador)
        $fechaHumana = Carbon::parse($this->fecha,'America/Santiago')->isoFormat('dddd D [de] MMMM YYYY');

        return $this
            ->subject("Programación Diaria 4°TOP — {$fechaHumana}")
            ->html($inlined);
    }
}
