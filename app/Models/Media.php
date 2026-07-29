<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'filename',
        'disk_path',
        'mime_type',
        'size',
        'alt',
    ];

    protected $appends = ['url', 'is_video'];

    public function getUrlAttribute(): string
    {
        return '/storage/' . $this->disk_path;
    }

    public function getIsVideoAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }
}
