<?php

namespace Database\Factories;

use App\Models\InterestRegion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InterestRegion>
 */
class InterestRegionFactory extends Factory
{
    protected $model = InterestRegion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->state();

        return [
            'name' => $name,
            'code' => strtoupper(Str::random(2)),
            'slug' => Str::slug($name),
            'sort_order' => fake()->numberBetween(1, 100),
            'status' => 'active',
        ];
    }

    public function country(): static
    {
        return $this->state(fn (): array => ['parent_id' => null]);
    }

    public function subdivision(?InterestRegion $country = null): static
    {
        return $this->state(function () use ($country): array {
            if ($country !== null) {
                return ['parent_id' => $country->id];
            }

            return ['parent_id' => InterestRegion::factory()->country()->create()->id];
        });
    }
}
