<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SlugRedirect extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'redirectable_type',
        'redirectable_id',
        'old_slug',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function redirectable(): MorphTo
    {
        return $this->morphTo();
    }
}
