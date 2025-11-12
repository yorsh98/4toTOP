<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailRecipient extends Model
{
    protected $fillable = ['email','nombre','activo','orden'];
    public function scopeActivos($q){ return $q->where('activo', true); }
}
