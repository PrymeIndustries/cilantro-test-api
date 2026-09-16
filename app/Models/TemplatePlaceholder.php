<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TemplatePlaceholder extends Model
{
    protected $fillable = [
        'text', 'value', 'model_key', 'model',
        'description', 'template_type_id', 'system'
    ];


    protected $casts = [
        'system' => 'boolean'
    ];

    public function scopeNonSystem(Builder $query, $value = true): void
    {
        $query->where('system', !$value);
    }


}
