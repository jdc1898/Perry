<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class InstallScriptController extends Controller
{
    public function __invoke(): Response
    {
        $script = file_get_contents(base_path('resources/install.sh'));

        return response($script, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="install.sh"')
            ->header('Cache-Control', 'no-cache, no-store');
    }
}
