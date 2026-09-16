<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;
    protected $appends = ['url', 'full_path', 'thumbnail_full_path', 'media_size'];

    public function url(): Attribute
    {
        return new Attribute(
            function () {
                return Storage::url($this->attributes['path']);
            },
        );
    }

    public function getFullPathAttribute(): string
    {
        return asset(Storage::url($this->attributes['path']));
    }

    public function getMediaSizeAttribute(): string
    {
        return Storage::size('public/' . $this->attributes['path']);
    }

    public function getThumbnailFullPathAttribute(): string
    {
        return $this->attributes['thumbnail'] ? asset(Storage::url($this->attributes['thumbnail'])) : '';

    }
}
