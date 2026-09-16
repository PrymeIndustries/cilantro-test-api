<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'active', 'final'
    ];

}
