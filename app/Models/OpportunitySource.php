<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpportunitySource extends Model
{
    protected $fillable = [
        'name',
        'base_url',
        'scraping_strategy',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scrapingRuns(): HasMany
    {
        return $this->hasMany(ScrapingRun::class, 'source_id');
    }
}
