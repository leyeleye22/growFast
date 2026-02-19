<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunitySuggestion extends Model
{
    protected $fillable = [
        'user_id',
        'grant_name',
        'award_amount_min',
        'award_amount_max',
        'application_link',
        'deadline',
        'location_eligibility',
        'description',
        'status',
    ];

    protected $casts = [
        'deadline' => 'date',
        'award_amount_min' => 'decimal:2',
        'award_amount_max' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
