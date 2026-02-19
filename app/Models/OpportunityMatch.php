<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityMatch extends Model
{
    protected $fillable = [
        'startup_id',
        'opportunity_id',
        'score',
        'score_breakdown',
    ];

    protected $casts = [
        'score' => 'integer',
        'score_breakdown' => 'array',
    ];

    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
