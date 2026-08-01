<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'feed_url',
        'tier',
        'is_active',
        'last_fetched_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'tier' => 'integer',
            'is_active' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
