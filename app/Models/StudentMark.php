<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMark extends Model
{
    protected $fillable = [
        'submission_id', 'teacher_id', 'student_id', 'category_id',
        'subject_id', 'marks_obtained', 'total_marks', 'submission_type_id',
        'term_id', 'academic_year_id'
    ];


    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function submission_type(): BelongsTo
    {
        return $this->belongsTo(SubmissionType::class);
    }

}
