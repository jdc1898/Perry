<?php

namespace App\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RevokeAgentController extends Controller
{
    public function __invoke(Agent $agent): RedirectResponse
    {
        $agent->update(['status' => 'revoked']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agent revoked.']);

        return back();
    }
}
