<?php

namespace Database\Factories;

use App\Models\AgentCheckResult;
use App\Models\AgentReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentCheckResultFactory extends Factory
{
    protected $model = AgentCheckResult::class;

    public function definition(): array
    {
        return [
            'report_id' => AgentReport::factory(),
            'name' => fake()->randomElement(['php', 'mysql', 'redis', 'system']),
            'status' => fake()->randomElement(['ok', 'warning', 'critical']),
            'message' => fake()->sentence(),
            'metrics' => null,
            'checked_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
        ];
    }

    public function ok(): static
    {
        return $this->state(['status' => 'ok']);
    }

    public function warning(): static
    {
        return $this->state(['status' => 'warning']);
    }

    public function critical(): static
    {
        return $this->state(['status' => 'critical']);
    }
}
