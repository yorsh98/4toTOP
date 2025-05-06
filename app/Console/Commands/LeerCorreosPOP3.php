<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webklex\IMAP\Facades\Client;

class LeerCorreosPOP3 extends Command
{
    protected $signature = 'correo:leer-ultimos';
    protected $description = 'Leer los últimos 5 correos desde POP3 y mostrar asunto, remitente y cuerpo';

    public function handle()
    {
        $this->info("Conectando al servidor de correo...");

        $client = Client::account('default'); // Usa la cuenta "default" de config/imap.php
        $client->connect();

        $folder = $client->getFolder('INBOX');

        $messages = $folder->messages()->all()->limit(5)->sortByDesc('date')->get();

        foreach ($messages as $message) {
            $this->line("====================================");
            $this->line("📧 Asunto: " . $message->getSubject());
            $this->line("👤 Remitente: " . $message->getFrom()[0]->mail);
            $this->line("🕒 Fecha: " . $message->getDate()->format('Y-m-d H:i:s'));

            $body = strip_tags($message->getHTMLBody() ?: $message->getTextBody());
            $preview = substr($body, 0, 200); // Muestra los primeros 200 caracteres
            $this->line("📝 Cuerpo (inicio): " . $preview);
        }

        $this->info("Fin de lectura de correos.");
    }
}
