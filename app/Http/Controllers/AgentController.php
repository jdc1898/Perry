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
            'name'        => ['required', 'string', 'max:100'],
            'public_key'  => ['required', 'string'],
            'fingerprint' => ['required', 'string', Rule::unique('agents', 'fingerprint')],
        ]);

        $agent = Agent::create($data);

        return redirect()->route('agents.index')
            ->with('flash', ['type' => 'success', 'message' => 'Agent registered successfully.']);
    }

    public function show(Agent $agent): Response
    {
        $reports = $agent->allReports()
            ->with('checkResults')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'hostname'    => $r->hostname,
                'reported_at' => $r->reported_at,
                'status'      => $r->overallStatus(),
                'checks'      => $r->checkResults->map(fn ($c) => [
                    'name'       => $c->name,
                    'status'     => $c->status,
                    'message'    => $c->message,
                    'metrics'    => $c->metrics,
                    'checked_at' => $c->checked_at,
                ]),
            ]);

        return Inertia::render('agents/Show', [
            'agent'   => $this->agentDetail($agent),
            'reports' => $reports,
        ]);
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

        return back()->with('flash', ['type' => 'success', 'message' => 'Configuration saved.']);
    }

    public function revoke(Agent $agent): RedirectResponse
    {
        $agent->update(['status' => 'revoked']);

        return back()->with('flash', ['type' => 'success', 'message' => 'Agent revoked.']);
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();

        return redirect()->route('agents.index')
            ->with('flash', ['type' => 'success', 'message' => 'Agent deleted.']);
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
