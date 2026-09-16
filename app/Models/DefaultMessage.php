<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultMessage extends Model
{
    protected $fillable = ['module', 'default', 'description', 'message', 'details'];
    protected $casts = [
        'details' => 'array'
    ];
    protected $attributes = [
        'details' => "{}"
    ];
}
