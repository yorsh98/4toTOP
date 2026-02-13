<?php
/*OBSOLETO*/ 
/* APARTADADO SIN USO YA QUE AHORA TODO SE LEE DESDE LA BD PERO SE DEJA EN CASO DE REUTILIZAR 
EL CODIGO EN OTRO IMPLEMENTACION */
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

        // Credenciales hardcodeadas directamente
        $usuario = 'jtroncosor@pjud.cl';
        $password = 'J0rg3.tr0nc0501234';

        $mailbox = new Mailbox(
            '{pop3.mail.pjud:110/pop3/notls}INBOX',
            $usuario,      // Usuario directo
            $password,     // Contraseña directa
            null,
            'US-ASCII'
        );

        try {
            $mailsIds = $mailbox->searchMailbox('ALL');

            if (!$mailsIds) {
                $this->info("No se encontraron correos.");
                return;
            }
            
            $this->info("Encontrados " . count($mailsIds) . " correos");
            rsort($mailsIds);

            $correoEncontrado = false;

            foreach ($mailsIds as $mailId) {
                $mail = $mailbox->getMail($mailId);
                $asunto = strtoupper(trim($mail->subject));

                $this->info("Revisando: " . $mail->subject);

                if (str_starts_with($asunto, 'PROGRAMACIÓN DE AUDIENCIAS')) {
                    $contenidoOriginal = $mail->textHtml ?: nl2br($mail->textPlain);

                    // Limpiar el contenido HTML quitando párrafos con "De:" o "Para:"
                    $contenidoFiltrado = preg_replace('/<p[^>]*>.*?(De|Para)\s*:.+?<\/p>/i', '', $contenidoOriginal);

                    // Guardar el HTML limpio en un archivo
                    File::put(storage_path('app/ultima_audiencia_email.html'), $contenidoFiltrado);

                    $this->info("✅ Correo guardado como HTML limpio en: storage/app/ultima_audiencia_email.html");
                    $correoEncontrado = true;
                    break;
                }
            }

            if (!$correoEncontrado) {
                $this->info("No se encontró ningún correo con asunto 'PROGRAMACIÓN DE AUDIENCIAS'.");
            }

        } catch (\PhpImap\Exceptions\ConnectionException $ex) {
            $this->error("❌ Error de conexión: " . $ex->getMessage());
            $this->error("Verifica: ");
            $this->error("- Que el servidor pop3.mail.pjud esté activo");
            $this->error("- Que el puerto 110 esté abierto");
            $this->error("- Que las credenciales sean correctas");
        } catch (\Exception $ex) {
            $this->error("❌ Error general: " . $ex->getMessage());
        }
    }
}