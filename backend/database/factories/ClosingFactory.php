<?php

namespace Database\Factories;

use App\Models\Closing;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Closing>
 */
class ClosingFactory extends Factory
{
    protected $model = Closing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true).' Closing',
            'lead_id' => Lead::factory(),
            'closing_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'status' => 'pending',
        ];
    }
}
