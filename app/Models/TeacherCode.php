<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherCode extends Model
{
    protected $fillable = [ 'code', 'teacher_id' ];

    protected $casts = [
        'code' => 'encrypted'
    ];


    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

}
