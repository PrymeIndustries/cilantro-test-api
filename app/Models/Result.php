<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Result extends Model
{
    protected $fillable = [
        'student_id', 'category_id',
        'marks_obtained', 'marks_obtained_ptg', 'total_marks',
        'average', 'class_rank', 'overall_rank', 'promoted', 
        'decision', 'remark', 'published', 'passed_examination',
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

    public function report_sheet(): HasOne
    {
        return $this->hasOne(ReportSheet::class);
    }

}
