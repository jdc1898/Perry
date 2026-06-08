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

        // Last report per agent with overall status
        $agentStatuses = Agent::where('status', '!=', 'revoked')
            ->orderBy('name')
            ->get()
            ->map(function (Agent $agent) {
                $lastReport = AgentReport::where('agent_id', $agent->id)
                    ->with('checkResults')
                    ->orderByDesc('reported_at')
                    ->first();

                return [
                    'id'           => $agent->id,
                    'name'         => $agent->name,
                    'hostname'     => $agent->hostname,
                    'status'       => $agent->status,
                    'is_online'    => $agent->isOnline(),
                    'last_seen_at' => $agent->last_seen_at,
                    'last_report'  => $lastReport ? [
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
}
