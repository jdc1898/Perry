<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentCheckResult;
use App\Models\AgentReport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentReportController extends Controller
{
    public function __invoke(Request $request, string $agentId): JsonResponse
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');

        $data = $request->json()->all();

        // Always normalise to UTC so DB comparisons work regardless of agent timezone
        $reportedAt = isset($data['timestamp'])
            ? Carbon::parse($data['timestamp'])->utc()
            : now()->utc();

        DB::transaction(function () use ($agent, $data, $reportedAt) {
            $report = AgentReport::create([
                'agent_id' => $agent->id,
                'hostname' => $data['hostname'] ?? $agent->hostname ?? 'unknown',
                'reported_at' => $reportedAt,
            ]);

            $checks = $data['checks'] ?? [];
            foreach ($checks as $check) {
                AgentCheckResult::create([
                    'report_id' => $report->id,
                    'name' => $check['name'],
                    'status' => $check['status'],
                    'message' => $check['message'] ?? '',
                    'metrics' => $check['metrics'] ?? null,
                    'checked_at' => isset($check['timestamp'])
                        ? Carbon::parse($check['timestamp'])->utc()
                        : $reportedAt,
                ]);
            }

            $update = [
                'last_seen_at' => now(),
                'hostname' => $data['hostname'] ?? $agent->hostname,
            ];

            if (! empty($data['binary_hash'])) {
                $update['reported_binary_hash'] = $data['binary_hash'];
            }

            if (! empty($data['version'])) {
                $update['agent_version'] = $data['version'];
            }

            $agent->update($update);
        });

        return response()->json(['ok' => true], 201);
    }
}
