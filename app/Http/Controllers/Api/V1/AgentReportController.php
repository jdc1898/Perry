<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentCheckResult;
use App\Models\AgentReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentReportController extends Controller
{
    public function store(Request $request, string $agentId): JsonResponse
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');

        $data = $request->json()->all();

        $reportedAt = isset($data['timestamp'])
            ? \Carbon\Carbon::parse($data['timestamp'])
            : now();

        DB::transaction(function () use ($agent, $data, $reportedAt) {
            $report = AgentReport::create([
                'agent_id'    => $agent->id,
                'hostname'    => $data['hostname'] ?? $agent->hostname ?? 'unknown',
                'reported_at' => $reportedAt,
            ]);

            $checks = $data['checks'] ?? [];
            foreach ($checks as $check) {
                AgentCheckResult::create([
                    'report_id'  => $report->id,
                    'name'       => $check['name'],
                    'status'     => $check['status'],
                    'message'    => $check['message'] ?? '',
                    'metrics'    => $check['metrics'] ?? null,
                    'checked_at' => isset($check['timestamp'])
                        ? \Carbon\Carbon::parse($check['timestamp'])
                        : $reportedAt,
                ]);
            }

            $agent->update([
                'last_seen_at' => $reportedAt,
                'hostname'     => $data['hostname'] ?? $agent->hostname,
            ]);
        });

        return response()->json(['ok' => true], 201);
    }
}
