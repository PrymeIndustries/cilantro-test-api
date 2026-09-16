<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectPeriod extends Model
{
    protected $fillable = [
        'timetable_id', 'sub_category_id', 'subject_id', 'teacher_id', 
        'day', 'start_time', 'end_time', 'time', 
    ];

    
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

}
