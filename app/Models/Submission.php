<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = [
        'description', 'script', 'teacher_id', 'category_id',
        'subject_id', 'total_marks', 'status', 'approved',
        'submission_type_id', 'term_id', 'academic_year_id'
    ];


    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function submission_type(): BelongsTo
    {
        return $this->belongsTo(SubmissionType::class);
    }

    public function student_marks(): HasMany
    {
        return $this->hasMany(StudentMark::class);
    }

}
