<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSignature extends Model
{
    protected $fillable = ['nombre','html','activo','orden','from_env','mailer'];

    public function scopeActivas($q){ return $q->where('activo', true); }

    public function resolveFromEmail(): ?string
    {
        return $this->from_env
            ? (env($this->from_env) ?: config('mail.from.address'))
            : config('mail.from.address');
    }

    public function resolveMailerName(): string
    {
        return $this->mailer ?: config('mail.default', 'smtp');
    }
}