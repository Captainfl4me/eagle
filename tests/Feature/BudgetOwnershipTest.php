<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_another_users_budget()
    {
        $owner = User::create([
            'username' => 'owner',
            'password' => bcrypt('password'),
        ]);

        $other = User::create([
            'username' => 'other',
            'password' => bcrypt('password'),
        ]);

        $budget = Budget::create([
            'user_id' => $owner->id,
            'name' => 'Secret Budget',
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($other)->get(route('budgets.show', $budget->id));
        $response->assertStatus(404);
    }
}
