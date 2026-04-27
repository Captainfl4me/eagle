<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Budget;

#[Fillable(['username', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the budgets owned by the user.
     */
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
