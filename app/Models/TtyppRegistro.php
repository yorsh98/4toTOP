<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TtyppRegistro extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ttypp_registros';

    protected $fillable = [
        'rit',
        'ruc',
        'tipo_audiencia',
        'numero_dias',
        'cantidad',
        'modalidad',
        'encargada_ti_mp',
        'encargada_ti_d',
        'encargada_zoom',
        'acta',
        'sala',
        'jueces',
        'fecha_asignacion',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_asignacion' => 'date',
    ];

    public function historial()
    {
        return $this->hasMany(TtyppRegistroHistorial::class, 'ttypp_registro_id')->orderBy('created_at', 'desc');
    }

    protected static function booted()
    {
        static::updating(function ($registro) {
            $dirty = $registro->getDirty();
            $changes = [];
            foreach ($dirty as $key => $newValue) {
                if (in_array($key, ['updated_at', 'updated_by'])) {
                    continue;
                }
                $oldValue = $registro->getOriginal($key);
                if ($oldValue != $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue instanceof \DateTimeInterface ? $oldValue->format('Y-m-d') : $oldValue,
                        'new' => $newValue instanceof \DateTimeInterface ? $newValue->format('Y-m-d') : $newValue,
                    ];
                }
            }

            if (! empty($changes)) {
                TtyppRegistroHistorial::create([
                    'ttypp_registro_id' => $registro->id,
                    'user_id' => auth()->id(),
                    'cambios' => $changes,
                    'created_at' => now(),
                ]);
            }
        });
    }
}
