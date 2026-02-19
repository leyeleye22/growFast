<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedOpportunity extends Model
{
    protected $fillable = [
        'startup_id',
        'opportunity_id',
        'last_reminder_at',
    ];

    protected function casts(): array
    {
        return [
            'last_reminder_at' => 'datetime',
        ];
    }

    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
