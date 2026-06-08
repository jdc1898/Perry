<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BinaryController extends Controller
{
    private const DISK = 'local';
    private const FILE = 'perry';

    public function download(): StreamedResponse
    {
        abort_unless(Storage::disk(self::DISK)->exists(self::FILE), 404, 'Binary not yet uploaded.');

        return Storage::disk(self::DISK)->download(self::FILE, 'perry', [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function edit(): Response
    {
        $exists   = Storage::disk(self::DISK)->exists(self::FILE);
        $hashPath = Storage::disk(self::DISK)->path(self::FILE . '.sha256');
        $hash     = ($exists && file_exists($hashPath)) ? trim(file_get_contents($hashPath)) : null;

        return Inertia::render('settings/Binary', [
            'binary' => [
                'exists'      => $exists,
                'size'        => $exists ? Storage::disk(self::DISK)->size(self::FILE) : null,
                'modified_at' => $exists
                    ? date('Y-m-d H:i:s', Storage::disk(self::DISK)->lastModified(self::FILE))
                    : null,
                'hash'        => $hash,
            ],
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'binary' => ['required', 'file', 'max:102400'], // 100 MB max
        ]);

        $content = $request->file('binary')->getContent();
        $hash    = hash('sha256', $content);

        Storage::disk(self::DISK)->put(self::FILE, $content);
        Storage::disk(self::DISK)->put(self::FILE . '.sha256', $hash);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agent binary uploaded.']);

        return to_route('binary.edit');
    }
}
