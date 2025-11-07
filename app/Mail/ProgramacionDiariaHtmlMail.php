<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue; // si luego usarás colas
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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

    public function __construct(
        string $fecha,
        array $juicios,
        array $lecturas,
        array $cortas,
        array $juecesAusentes,
        ?string $turno1 = null,
        ?string $turno2 = null,
        ?string $turno3 = null,
    ) {
        $this->fecha = $fecha;
        $this->juicios = $juicios;
        $this->lecturas = $lecturas;
        $this->cortas = $cortas;
        $this->juecesAusentes = $juecesAusentes;
        $this->turno1 = $turno1;
        $this->turno2 = $turno2;
        $this->turno3 = $turno3;
    }

    public function envelope(): Envelope
    {
        $fechaHumana = Carbon::parse($this->fecha, 'America/Santiago')->isoFormat('dddd D [de] MMMM YYYY');
        return new Envelope(subject: "Programación Diaria 4°TOP — {$fechaHumana}");
    }

    public function content(): Content
    {
        $fechaHuman = Carbon::parse($this->fecha, 'America/Santiago')->isoFormat('dddd D [de] MMMM YYYY');

        return new Content(
            view: 'emails.programacion-diaria-html',
            with: [
                'fechaHuman'     => $fechaHuman,
                'juicios'        => $this->juicios,
                'lecturas'       => $this->lecturas,
                'cortas'         => $this->cortas,
                'juecesAusentes' => $this->juecesAusentes,
                'turno1'         => $this->turno1,
                'turno2'         => $this->turno2,
                'turno3'         => $this->turno3,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
        // 1) Renderiza tu Blade a HTML (con tu <style> interno)
        $html = view('emails.programacion-diaria-html', [
            'fechaHuman'     => \Carbon\Carbon::parse($this->fecha,'America/Santiago')->isoFormat('dddd D [de] MMMM YYYY'),
            'juicios'        => $this->juicios,
            'lecturas'       => $this->lecturas,
            'cortas'         => $this->cortas,
            'juecesAusentes' => $this->juecesAusentes,
            'turno1'         => $this->turno1,
            'turno2'         => $this->turno2,
            'turno3'         => $this->turno3,
        ])->render();

        // 2) Inlinar CSS
        $inliner = new CssToInlineStyles();
        $inlined = $inliner->convert($html);

        // 3) (Recomendado) reemplaza íconos de Font Awesome por emojis/Unicode en la vista
        // (muchos correos bloquean las fuentes/links externos)

        return $this->subject("Programación Diaria 4°TOP — ".$this->fecha)
                    ->from(env('MAIL_FROM_ADDRESS'), "Programación Diaria 4°TOP (".now('America/Santiago')->format('d-m-Y').")")
                    ->html($inlined);
    }
}
