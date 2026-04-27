<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetMonth extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'month',
        'budgeted_amount',
        'realized_amount',
    ];

    protected $casts = [
        'month' => 'date',
        'budgeted_amount' => 'decimal:2',
        'realized_amount' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
