<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpImap\Mailbox;
use Illuminate\Support\Facades\File;

class LeerCorreosPOP3 extends Command
{
    protected $signature = 'correo:leer-ultimos';
    protected $description = 'Leer y guardar el correo de audiencias como HTML limpio';

    public function handle()
    {
        $this->info("Conectando por POP3 sin cifrado...");

        $mailbox = new Mailbox(
            '{pop3.mail.pjud:110/pop3/notls}INBOX',
            env('MAIL_USERNAME'),
            env('MAIL_PASSWORD'),
            null,
            'US-ASCII'
        );

        try {
            $mailsIds = $mailbox->searchMailbox('ALL');

            if (!$mailsIds) {
                $this->info("No se encontraron correos.");
                return;
            }
            
            rsort($mailsIds);

            foreach ($mailsIds as $mailId) {
                $mail = $mailbox->getMail($mailId);

                if (str_starts_with(strtoupper(trim($mail->subject)), 'PROGRAMACIÓN DE AUDIENCIAS')) {
                    $contenidoOriginal = $mail->textHtml ?: nl2br($mail->textPlain);

                    // Limpiar el contenido HTML quitando párrafos con "De:" o "Para:"
                    $contenidoFiltrado = preg_replace('/<p[^>]*>.*?(De|Para)\s*:.+?<\/p>/i', '', $contenidoOriginal);

                    // Guardar el HTML limpio en un archivo
                    File::put(storage_path('app/ultima_audiencia_email.html'), $contenidoFiltrado);

                    $this->info("✅ Correo guardado como HTML limpio en: storage/app/ultima_audiencia_email.html");
                    return;
                }
            }

            $this->info("No se encontró ningún correo con asunto 'PROGRAMACIÓN DE AUDIENCIAS'.");

        } catch (\PhpImap\Exceptions\ConnectionException $ex) {
            $this->error("❌ Error de conexión: " . $ex->getMessage());
        }
    }
}
