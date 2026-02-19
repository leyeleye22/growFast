<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityCountry extends Model
{
    protected $table = 'opportunity_country';

    protected $fillable = ['opportunity_id', 'country_code'];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
