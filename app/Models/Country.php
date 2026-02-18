<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Country extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'name'];

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(
            Opportunity::class,
            'opportunity_country',
            'country_code',
            'opportunity_id',
            'code',
            'id'
        );
    }
}
