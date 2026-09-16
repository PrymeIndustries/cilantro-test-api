<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAttendance extends Model
{
    protected $fillable = [
        'attendance_id', 'teacher_id', 'present', 'academic_year_id'
    ];


    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

}