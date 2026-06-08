<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function index(): Response
    {
        $agents = Agent::orderBy('name')
            ->withCount(['allReports as report_count'])
            ->get()
            ->map(fn (Agent $a) => $this->agentSummary($a));

        return Inertia::render('agents/Index', ['agents' => $agents]);
    }

    public function create(): Response
    {
        return Inertia::render('agents/Create', [
            'appUrl' => config('app.url'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id'          => ['required', 'uuid', Rule::unique('agents', 'id')],
            'name'        => ['required', 'string', 'max:100'],
            'public_key'  => ['required', 'string'],
            'fingerprint' => ['required', 'string', Rule::unique('agents', 'fingerprint')],
        ]);

        $agent = Agent::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agent registered successfully.']);

        return redirect()->route('agents.index');
    }

    public function show(Agent $agent): Response
    {
        $days  = 30;
        $since = now()->subDays($days - 1)->startOfDay();

        $reports = $agent->allReports()
            ->with(['checkResults:report_id,status'])
            ->where('reported_at', '>=', $since)
            ->select(['id', 'reported_at'])
            ->orderBy('reported_at')
            ->get();

        // Map reports to date => slot (5-min window) => worst status
        $byDate = [];
        foreach ($reports as $report) {
            $date   = $report->reported_at->toDateString();
            $slot   = (int) floor(($report->reported_at->hour * 60 + $report->reported_at->minute) / 5);
            $status = $report->overallStatus();

            $byDate[$date][$slot] = isset($byDate[$date][$slot])
                ? $this->worstStatus($byDate[$date][$slot], $status)
                : $status;
        }

        // Build timeline newest-first, all 30 days
        $timeline = [];
        for ($i = 0; $i < $days; $i++) {
            $date   = now()->subDays($i)->toDateString();
            $slots  = array_fill(0, 288, null);
            foreach ($byDate[$date] ?? [] as $slot => $status) {
                $slots[$slot] = $status;
            }
            $timeline[] = ['date' => $date, 'slots' => $slots];
        }

        return Inertia::render('agents/Show', [
            'agent'    => $this->agentDetail($agent),
            'timeline' => $timeline,
        ]);
    }

    private function worstStatus(string $a, string $b): string
    {
        $order = ['critical' => 3, 'warning' => 2, 'unknown' => 1, 'ok' => 0];
        return ($order[$a] ?? 0) >= ($order[$b] ?? 0) ? $a : $b;
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:100'],
            'check_interval'       => ['required', 'integer', 'min:10', 'max:3600'],
            'config_poll_interval' => ['required', 'integer', 'min:60', 'max:3600'],
            'php_config'           => ['nullable', 'array'],
            'mysql_config'         => ['nullable', 'array'],
            'reverb_config'        => ['nullable', 'array'],
            'redis_config'         => ['nullable', 'array'],
        ]);

        $agent->update(array_merge($data, [
            'config_version' => $agent->config_version + 1,
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuration saved.']);

        return back();
    }

    public function revoke(Agent $agent): RedirectResponse
    {
        $agent->update(['status' => 'revoked']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agent revoked.']);

        return back();
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agent deleted.']);

        return redirect()->route('agents.index');
    }

    private function agentSummary(Agent $agent): array
    {
        return [
            'id'           => $agent->id,
            'name'         => $agent->name,
            'hostname'     => $agent->hostname,
            'status'       => $agent->status,
            'is_online'    => $agent->isOnline(),
            'last_seen_at' => $agent->last_seen_at,
            'report_count' => $agent->report_count,
        ];
    }

    private function agentDetail(Agent $agent): array
    {
        return [
            'id'                   => $agent->id,
            'name'                 => $agent->name,
            'hostname'             => $agent->hostname,
            'fingerprint'          => $agent->fingerprint,
            'status'               => $agent->status,
            'is_online'            => $agent->isOnline(),
            'last_seen_at'         => $agent->last_seen_at,
            'check_interval'       => $agent->check_interval,
            'config_poll_interval' => $agent->config_poll_interval,
            'config_version'       => $agent->config_version,
            'php_config'           => $agent->php_config    ?? ['enabled' => false, 'fpm_socket' => '', 'status_url' => ''],
            'mysql_config'         => $agent->mysql_config  ?? ['enabled' => false, 'dsn' => '', 'check_replication' => false],
            'reverb_config'        => $agent->reverb_config ?? ['enabled' => false, 'host' => '127.0.0.1', 'port' => 8080],
            'redis_config'         => $agent->redis_config  ?? ['enabled' => false, 'addr' => '127.0.0.1:6379', 'password' => '', 'db' => 0],
            'created_at'           => $agent->created_at,
        ];
    }
}
