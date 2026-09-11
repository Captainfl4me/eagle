<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    /**
     * Get the months belonging to the budget.
     */
    public function months()
    {
        return $this->hasMany(BudgetMonth::class);
    }

    /**
     * Get the reallocations made into this budget.
     */
    public function reallocationsIn(): HasMany
    {
        return $this->hasMany(Reallocation::class, 'recipient_budget_id');
    }

    /**
     * Get the reallocations taken from this budget.
     */
    public function reallocationsOut(): HasMany
    {
        return $this->hasMany(Reallocation::class, 'source_budget_id');
    }

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'start_month',
        'start_amount',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_month' => 'date',
        'start_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the budget.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
