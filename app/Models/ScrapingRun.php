<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScrapingRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapingRun extends Model
{
    protected $fillable = [
        'source_id',
        'status',
        'items_found',
    ];

    protected $casts = [
        'items_found' => 'integer',
        'status' => ScrapingRunStatus::class,
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(OpportunitySource::class, 'source_id');
    }

    public function scrapedEntries(): HasMany
    {
        return $this->hasMany(ScrapedEntry::class);
    }
}
