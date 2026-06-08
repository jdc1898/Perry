<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentConfigController extends Controller
{
    public function show(Request $request, string $agentId): JsonResponse
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');

        // Activate on first successful config fetch
        if ($agent->status === 'pending') {
            $agent->update(['status' => 'active']);
        }

        $agent->update(['last_seen_at' => now()]);

        return response()->json($agent->toRemoteConfig());
    }
}
