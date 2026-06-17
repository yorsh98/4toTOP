<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TtyppRegistroHistorial extends Model
{
    public $timestamps = false;

    protected $table = 'ttypp_registro_historials';

    protected $fillable = [
        'ttypp_registro_id',
        'user_id',
        'cambios',
        'created_at',
    ];

    protected $casts = [
        'cambios' => 'array',
        'created_at' => 'datetime',
    ];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(TtyppRegistro::class, 'ttypp_registro_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
