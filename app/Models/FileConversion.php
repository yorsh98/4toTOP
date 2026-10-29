<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileConversion extends Model
{
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    protected $fillable = [
        'token', 'area', 'format', 'status', 'original_name', 'result_name',
        'input_path', 'output_path', 'user_id', 'actor_name', 'ip_address',
        'session_hash', 'user_agent', 'error_message', 'finished_at', 'expires_at', 'files_deleted_at',
        'stage', 'progress', 'engine', 'input_sha256', 'output_sha256', 'notice', 'options',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'finished_at' => 'datetime', 'expires_at' => 'datetime', 'files_deleted_at' => 'datetime'];
    }
}
