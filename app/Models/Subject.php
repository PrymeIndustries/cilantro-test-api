<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'name', 'code', 'coefficient', 'category_id'
    ];

//    protected $casts = [
//        'periods' => 'array'
//    ];


    public function teacher_subjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function student_subjects(): HasMany
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(SubjectPeriod::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
    
    public function marks(): HasMany
    {
        return $this->hasMany(StudentMark::class);
    }

}
