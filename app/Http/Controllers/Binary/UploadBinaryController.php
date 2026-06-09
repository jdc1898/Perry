<?php

namespace App\Http\Controllers\Binary;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UploadBinaryController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'binary' => ['required', 'file', 'max:102400'],
        ]);

        $content = $request->file('binary')->getContent();
        $hash = hash('sha256', $content);

        Storage::disk('local')->put('perry', $content);
        Storage::disk('local')->put('perry.sha256', $hash);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agent binary uploaded.']);

        return to_route('binary.edit');
    }
}
