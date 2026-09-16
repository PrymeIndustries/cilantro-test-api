<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSubject extends Model
{

    protected $fillable = [
        'teacher_id', 'subject_id', 'academic_year_id'
    ];


    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }

    public function subject() {
        return $this->belongsTo(Subject::class);
    }

}
