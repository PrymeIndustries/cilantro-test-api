<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'date', 'subject_id', 'teacher_id', 'user_id', 'module', 'academic_year_id'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacherAttendances()
    {
        return $this->hasMany(TeacherAttendance::class);
    }

    public function studentAttendances()
    {
        return $this->hasMany(StudentAttendance::class);
    }

}