<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCheckResult extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['report_id', 'name', 'status', 'message', 'metrics', 'checked_at'];

    protected $casts = [
        'metrics' => 'array',
        'checked_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(AgentReport::class);
    }
}
