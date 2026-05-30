<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->company().' Application',
            'slug' => fake()->unique()->slug(),
            'owner_user_id' => User::factory(),
            'pipeline_phase' => 1,
            'lead_status' => 'active',
            'lead_stage' => '1',
            'status' => 'active',
        ];
    }
}
