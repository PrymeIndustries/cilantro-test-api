<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'name', 'parameters', 'slug'
    ];

    protected $casts = [
        'parameters' => 'array'
    ];


}
