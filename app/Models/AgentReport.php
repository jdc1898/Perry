<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentReport extends Model
{
    public $timestamps = false;

    protected $fillable = ['agent_id', 'hostname', 'reported_at'];

    protected $casts = [
        'reported_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function checkResults(): HasMany
    {
        return $this->hasMany(AgentCheckResult::class, 'report_id');
    }

    public function overallStatus(): string
    {
        $statuses = $this->checkResults->pluck('status');

        if ($statuses->contains('critical')) return 'critical';
        if ($statuses->contains('warning'))  return 'warning';
        if ($statuses->contains('unknown'))  return 'unknown';

        return 'ok';
    }
}
