<?php

namespace App\Console\Commands;

use App\NotificationsV2\Application\Services\BirthdayNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class SendBirthdayGreetings extends Command
{
    protected $signature = 'notificaciones:cumpleanos {fecha? : Y-m-d} {--dry-run : muestra a quienes se enviaria, sin enviar}';

    protected $description = 'Envía saludos y recordatorios de cumpleaños';

    public function handle(BirthdayNotifier $notifier): int
    {
        try {
            $value = $this->argument('fecha');
            $date = $value ? Carbon::createFromFormat('!Y-m-d', $value, 'America/Santiago') : now('America/Santiago')->startOfDay();
            if ($value && (! $date || $date->format('Y-m-d') !== $value)) {
                throw new \InvalidArgumentException;
            }
        } catch (Throwable $exception) {
            $this->error('Fecha inválida; use Y-m-d.');

            return self::FAILURE;
        }
        $messages = $notifier->notify($date, (bool) $this->option('dry-run'));
        if ($this->option('dry-run')) {
            foreach ($messages as $message) {
                $this->line($message['type'].': '.$message['name'].' <'.$message['email'].'>');
            }
        }
        $this->info('Procesados: '.count($messages));

        return self::SUCCESS;
    }
}
