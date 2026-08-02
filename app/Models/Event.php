<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'time',      // engaged ms on page + max scroll % (value = ms, target = scroll%)
        'click',     // click on a [data-track] element
        'outbound',  // click on an external link
        'feature',   // product events: quiz:start, poll:results, map:filter, share:x ...
    ];

    protected $fillable = [
        'session',
        'type',
        'path',
        'target',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
