<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSubject extends Model
{
    protected $fillable = [
        'student_id', 'subject_id', 'academic_year_id'
    ];


    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function subject() {
        return $this->belongsTo(Subject::class);
    }

}
