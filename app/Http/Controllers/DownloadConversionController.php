<?php

namespace App\Http\Controllers;

use App\Models\FileConversion;
use Illuminate\Support\Facades\Storage;

class DownloadConversionController extends Controller
{
    public function __invoke(FileConversion $conversion)
    {
        abort_unless(
            $conversion->area === 'frontoffice'
                ? hash_equals($conversion->session_hash, hash('sha256', session()->getId()))
                : auth()->check() && auth()->id() === $conversion->user_id && auth()->user()->can('navbar.conversiones.visible'),
            403
        );

        abort_unless($conversion->status === 'ready' && $conversion->expires_at?->isFuture() && $conversion->output_path, 404);

        $disk = Storage::disk(config('conversions.disk'));
        abort_unless($disk->exists($conversion->output_path), 404);

        return $disk->download($conversion->output_path, $conversion->result_name);
    }
}
