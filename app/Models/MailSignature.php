<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSignature extends Model
{
    protected $fillable = ['nombre', 'html', 'activo', 'orden', 'from_env', 'mailer'];

    public function scopeActivas($q)
    {
        return $q->where('activo', true);
    }

    public function resolveFromEmail(): ?string
    {
        $mailer = $this->resolveMailerName();
        $from = config("mail.mailers.{$mailer}.username") ?: config('mail.from.address');

        return filter_var($from, FILTER_VALIDATE_EMAIL)
            ? $from
            : config('mail.from.address');
    }

    public function resolveMailerName(): string
    {
        return $this->mailer ?: config('mail.default', 'smtp');
    }
}
