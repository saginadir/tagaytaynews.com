<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $fillable = [
        'question',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('sort_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function totalVotes(): int
    {
        return (int) $this->options->sum('votes');
    }

    public static function voterHash(string $ip, int $pollId): string
    {
        return hash('sha256', $ip.'|poll|'.$pollId.'|'.config('app.key'));
    }
}
