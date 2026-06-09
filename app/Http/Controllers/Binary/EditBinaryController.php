<?php

namespace App\Http\Controllers\Binary;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EditBinaryController extends Controller
{
    public function __invoke(): Response
    {
        $exists = Storage::disk('local')->exists('perry');
        $hashPath = Storage::disk('local')->path('perry.sha256');
        $hash = ($exists && file_exists($hashPath)) ? trim(file_get_contents($hashPath)) : null;

        return Inertia::render('settings/Binary', [
            'binary' => [
                'exists' => $exists,
                'size' => $exists ? Storage::disk('local')->size('perry') : null,
                'modified_at' => $exists
                    ? date('Y-m-d H:i:s', Storage::disk('local')->lastModified('perry'))
                    : null,
                'hash' => $hash,
            ],
        ]);
    }
}
