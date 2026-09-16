<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSheet extends Model
{
    protected $fillable = [
      'student_id', 'result_id', 'sheet',
      'term_id', 'academic_year_id'
    ];

    protected $casts = [
        'sheet' => 'encrypted'
    ];


    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

}
