<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCode extends Model
{
    protected $fillable = [ 'code', 'student_id' ];

    protected $casts = [
        'code' => 'encrypted'
    ];


    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
