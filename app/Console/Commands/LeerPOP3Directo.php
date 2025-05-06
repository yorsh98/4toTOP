<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpImap\Mailbox;

class LeerPOP3Directo extends Command
{
    protected $signature = 'correo:leer-pop3';
    protected $description = 'Leer correos vía POP3 directamente con php-imap';

    public function handle()
    {
        $this->info("Conectando por POP3 sin cifrado...");

        $mailbox = new Mailbox(
            '{pop3.mail.pjud:110/pop3/notls}INBOX', // conexión directa sin TLS
            env('MAIL_USERNAME'),                  // usuario
            env('MAIL_PASSWORD'),                  // contraseña
            null,                                  // directorio de attachments
            'US-ASCII'                             // encoding
        );

        try {
            $mailsIds = $mailbox->searchMailbox('ALL');

            if (!$mailsIds) {
                $this->info("No se encontraron correos.");
                return;
            }

            // Obtener los últimos 5
           // $mailsIds = array_slice(array_reverse($mailsIds), 0, 5);

            foreach ($mailsIds as $mailId) {
                $mail = $mailbox->getMail($mailId);
            
                if (str_starts_with(strtoupper(trim($mail->subject)), 'PROGRAMACIÓN DE AUDIENCIAS')) {
                    $this->line("====================================");
                    $this->info("📧 CORREO ENCONTRADO:");
                    $this->line("Asunto: " . $mail->subject);
                    $this->line("Remitente: " . $mail->fromAddress);
                    $this->line("Fecha: " . $mail->date);
            
                    $body = strip_tags($mail->textHtml ?: $mail->textPlain);
                    $preview = substr($body, 0, 50000);
                    $this->line("Contenido (inicio): " . $preview);
                    break; // Solo mostrar el más reciente
                }
            }
            

        } catch (\PhpImap\Exceptions\ConnectionException $ex) {
            $this->error("❌ No se pudo conectar: " . $ex->getMessage());
        }
    }
}
