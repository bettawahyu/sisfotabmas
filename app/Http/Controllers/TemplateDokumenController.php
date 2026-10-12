<?php

namespace App\Http\Controllers;

use App\Models\TemplateDokumen;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemplateDokumenController extends Controller
{
    /**
     * Any signed-in user may download an active template.
     */
    public function unduh(TemplateDokumen $template): StreamedResponse
    {
        $berkas = $template->berkasTerbaru;
        abort_if(! $template->is_active || $berkas === null, 404);

        return Storage::download($berkas->path, $berkas->nama_asli);
    }
}
