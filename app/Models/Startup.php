<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Startup extends Model
{
    use HasUuid, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'tagline',
        'description',
        'founding_date',
        'pitch_video_url',
        'website',
        'phone',
        'social_media',
        'industry',
        'customer_type',
        'stage',
        'country',
        'revenue_min',
        'revenue_max',
        'ownership_type',
        'funding_min',
        'funding_max',
        'funding_types',
        'preferred_industries',
        'preferred_stages',
        'preferred_countries',
        'deadline_min',
        'deadline_max',
    ];

    protected $casts = [
        'founding_date' => 'date',
        'deadline_min' => 'date',
        'deadline_max' => 'date',
        'revenue_min' => 'decimal:2',
        'revenue_max' => 'decimal:2',
        'funding_min' => 'decimal:2',
        'funding_max' => 'decimal:2',
        'funding_types' => 'array',
        'preferred_industries' => 'array',
        'preferred_stages' => 'array',
        'preferred_countries' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function opportunityMatches(): HasMany
    {
        return $this->hasMany(OpportunityMatch::class);
    }

    public function savedOpportunities(): HasMany
    {
        return $this->hasMany(SavedOpportunity::class);
    }
}
