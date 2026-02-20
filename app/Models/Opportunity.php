<?php



namespace App\Models;

use App\Enums\OpportunityStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasUuid, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'title',
        'description',
        'funding_type',
        'deadline',
        'funding_min',
        'funding_max',
        'status',
        'subscription_required_id',
        'is_premium',
        'external_url',
        'source',
        'eligibility_criteria',
    ];

    protected $casts = [
        'deadline' => 'date',
        'funding_min' => 'decimal:2',
        'funding_max' => 'decimal:2',
        'is_premium' => 'boolean',
        'status' => OpportunityStatus::class,
        'eligibility_criteria' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('not_expired', function (Builder $query): void {
            $query->where(function (Builder $q): void {
                $q->whereNull('deadline')
                    ->orWhere('deadline', '>', now());
            });
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
        OpportunityStatus::Active->value,
        OpportunityStatus::Pending->value,
    ]);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('deadline')
                ->orWhere('deadline', '>', now());
        });
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_premium', false)
            ->whereNull('subscription_required_id');
    }

    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_premium', true);
    }

    public function subscriptionRequired(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_required_id');
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class, 'opportunity_industry');
    }

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class, 'opportunity_stage');
    }

    public function countryCodes()
    {
        return $this->hasMany(OpportunityCountry::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(OpportunityAsset::class);
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
