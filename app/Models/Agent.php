<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Agent extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'name',
        'hostname',
        'public_key',
        'fingerprint',
        'status',
        'php_config',
        'mysql_config',
        'reverb_config',
        'redis_config',
        'system_config',
        'check_interval',
        'config_poll_interval',
        'config_version',
        'auto_update',
        'reported_binary_hash',
        'agent_version',
        'last_seen_at',
        'alerted_offline_at',
    ];

    protected $casts = [
        'php_config'           => 'array',
        'mysql_config'         => 'array',
        'reverb_config'        => 'array',
        'redis_config'         => 'array',
        'system_config'        => 'array',
        'auto_update'          => 'boolean',
        'last_seen_at'         => 'datetime',
        'alerted_offline_at'   => 'datetime',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(AgentReport::class)->latestOfMany('reported_at');
    }

    public function allReports(): HasMany
    {
        return $this->hasMany(AgentReport::class)->orderByDesc('reported_at');
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(10));
    }

    public function toRemoteConfig(): array
    {
        return [
            'version'     => $this->config_version,
            'revoked'     => $this->isRevoked(),
            'auto_update' => (bool) $this->auto_update,
            'binary_hash' => $this->currentBinaryHash(),
            'checks'      => [
                'php'    => $this->php_config    ?? $this->defaultPhpConfig(),
                'mysql'  => $this->mysql_config  ?? $this->defaultMysqlConfig(),
                'reverb' => $this->reverb_config ?? $this->defaultReverbConfig(),
                'redis'  => $this->redis_config  ?? $this->defaultRedisConfig(),
                'system' => $this->system_config ?? $this->defaultSystemConfig(),
            ],
            'intervals' => [
                'checks'      => $this->check_interval,
                'config_poll' => $this->config_poll_interval,
            ],
        ];
    }

    private function currentBinaryHash(): string
    {
        $path = Storage::disk('local')->path('perry.sha256');
        return file_exists($path) ? trim(file_get_contents($path)) : '';
    }

    private function defaultPhpConfig(): array
    {
        return ['enabled' => false, 'fpm_socket' => '', 'status_url' => ''];
    }

    private function defaultMysqlConfig(): array
    {
        return ['enabled' => false, 'dsn' => '', 'check_replication' => false];
    }

    private function defaultReverbConfig(): array
    {
        return ['enabled' => false, 'host' => '127.0.0.1', 'port' => 8080];
    }

    private function defaultRedisConfig(): array
    {
        return ['enabled' => false, 'addr' => '127.0.0.1:6379', 'password' => '', 'db' => 0];
    }

    private function defaultSystemConfig(): array
    {
        return [
            'enabled'              => false,
            'disk_paths'           => [],
            'network_interfaces'   => [],
            'cpu_warn_pct'         => 0,
            'ram_warn_pct'         => 0,
            'disk_warn_pct'        => 0,
        ];
    }
}
