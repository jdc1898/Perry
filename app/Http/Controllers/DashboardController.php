<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentCheckResult;
use App\Models\AgentReport;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $agents = Agent::all();

        $stats = [
            'total'    => $agents->count(),
            'active'   => $agents->where('status', 'active')->count(),
            'pending'  => $agents->where('status', 'pending')->count(),
            'revoked'  => $agents->where('status', 'revoked')->count(),
            'online'   => $agents->filter(fn (Agent $a) => $a->isOnline())->count(),
        ];

        $since24h = now()->subHours(4);

        $serverBinaryHash = $this->serverBinaryHash();

        // Last report per agent with overall status
        $agentStatuses = Agent::where('status', '!=', 'revoked')
            ->orderBy('name')
            ->get()
            ->map(function (Agent $agent) use ($since24h, $serverBinaryHash) {
                $lastReport = AgentReport::where('agent_id', $agent->id)
                    ->with('checkResults')
                    ->orderByDesc('reported_at')
                    ->first();

                // 4h uptime: 48 five-minute slots
                $reports24h = AgentReport::where('agent_id', $agent->id)
                    ->with('checkResults:report_id,status')
                    ->where('reported_at', '>=', $since24h)
                    ->select(['id', 'reported_at'])
                    ->orderBy('reported_at')
                    ->get();

                $slots = array_fill(0, 48, null);
                foreach ($reports24h as $report) {
                    $minutesAgo = $since24h->diffInMinutes($report->reported_at);
                    $slot       = (int) floor($minutesAgo / 5);
                    if ($slot < 0 || $slot >= 48) continue;
                    $status = $report->overallStatus();
                    $order  = ['critical' => 3, 'warning' => 2, 'ok' => 0];
                    if ($slots[$slot] === null || ($order[$status] ?? 0) > ($order[$slots[$slot]] ?? 0)) {
                        $slots[$slot] = $status;
                    }
                }

                $upgradePending = $serverBinaryHash !== ''
                    && $agent->reported_binary_hash !== null
                    && $agent->reported_binary_hash !== $serverBinaryHash;

                return [
                    'id'              => $agent->id,
                    'name'            => $agent->name,
                    'hostname'        => $agent->hostname,
                    'status'          => $agent->status,
                    'is_online'       => $agent->isOnline(),
                    'last_seen_at'    => $agent->last_seen_at,
                    'upgrade_pending' => $upgradePending,
                    'uptime_24h'      => $slots,
                    'last_report'     => $lastReport ? [
                        'status' => $lastReport->overallStatus(),
                        'checks' => $lastReport->checkResults->map(fn ($c) => [
                            'name'    => $c->name,
                            'status'  => $c->status,
                            'message' => $c->message,
                        ]),
                    ] : null,
                ];
            });

        // Recent critical/warning check results across all agents
        $recentIssues = AgentCheckResult::whereIn('status', ['critical', 'warning'])
            ->with('report.agent')
            ->orderByDesc('checked_at')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'agent_name'  => $c->report->agent->name ?? 'Unknown',
                'agent_id'    => $c->report->agent_id,
                'check'       => $c->name,
                'status'      => $c->status,
                'message'     => $c->message,
                'checked_at'  => $c->checked_at,
            ]);

        return Inertia::render('Dashboard', [
            'stats'         => $stats,
            'agentStatuses' => $agentStatuses,
            'recentIssues'  => $recentIssues,
        ]);
    }

    private function serverBinaryHash(): string
    {
        $path = \Illuminate\Support\Facades\Storage::disk('local')->path('perry.sha256');
        return file_exists($path) ? trim(file_get_contents($path)) : '';
    }
}
