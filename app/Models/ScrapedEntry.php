<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedEntry extends Model
{
    protected $fillable = [
        'scraping_run_id',
        'external_url',
        'raw_content',
        'content_hash',
        'processed',
        'duplicate_detected',
    ];

    protected $casts = [
        'processed' => 'boolean',
        'duplicate_detected' => 'boolean',
    ];

    public function scrapingRun(): BelongsTo
    {
        return $this->belongsTo(ScrapingRun::class);
    }
}
