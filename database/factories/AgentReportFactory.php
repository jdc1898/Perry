<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentReportFactory extends Factory
{
    protected $model = AgentReport::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'hostname' => fake()->domainWord().'.local',
            'reported_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
        ];
    }

    public function recent(): static
    {
        return $this->state(['reported_at' => now()->subMinutes(fake()->numberBetween(1, 30))]);
    }
}
