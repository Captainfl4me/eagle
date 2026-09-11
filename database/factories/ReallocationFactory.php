<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Reallocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reallocation>
 */
class ReallocationFactory extends Factory
{
    protected $model = Reallocation::class;

    public function definition(): array
    {
        $user = $this->faker->optional()->randomElement([User::factory()]);
        $budgets = Budget::factory()->count(2)->create(['user_id' => $user->id]);

        return [
            'recipient_budget_id' => $budgets[1]->id,
            'source_budget_id' => $budgets[0]->id,
            'month' => $this->faker->dateTimeThisYear()->format('Y-m-d'),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
