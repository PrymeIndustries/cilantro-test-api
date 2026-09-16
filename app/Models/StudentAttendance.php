<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    protected $fillable = [
        'attendance_id', 'student_id', 'present', 'term_id', 'academic_year_id'
    ];


    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

}