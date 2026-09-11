<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reallocation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'recipient_budget_id',
        'source_budget_id',
        'month',
        'amount',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'month' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the budget receiving the reallocation.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'recipient_budget_id');
    }

    /**
     * Get the budget the reallocation is taken from.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'source_budget_id');
    }
}
