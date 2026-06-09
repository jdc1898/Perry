<?php

namespace App\Http\Controllers\Binary;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadBinaryController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists('perry'), 404, 'Binary not yet uploaded.');

        return Storage::disk('local')->download('perry', 'perry', [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
