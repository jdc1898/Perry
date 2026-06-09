<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        $keypair = sodium_crypto_sign_keypair();

        return [
            'id' => fake()->uuid(),
            'name' => fake()->words(2, true),
            'hostname' => fake()->domainWord().'.local',
            'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
            'fingerprint' => fake()->sha256(),
            'status' => 'active',
            'check_interval' => 60,
            'config_poll_interval' => 300,
            'config_version' => 1,
            'auto_update' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function revoked(): static
    {
        return $this->state(['status' => 'revoked']);
    }
}
