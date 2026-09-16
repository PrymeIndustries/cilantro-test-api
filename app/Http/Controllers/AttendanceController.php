<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\TeacherAttendance;
use App\Models\StudentAttendance;
use App\Models\DisciplineRecord;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendances = Attendance::with(['user', 'teacher', 'subject'])->get();
        return response()->json($attendances);
    }

    public function getAttendances($ay_id)
    {
        $attendances = Attendance::with(['user', 'teacher', 'subject'])->where('academic_year_id', $ay_id)->get();
        return response()->json($attendances);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'teacher', 'subject', 'studentAttendances.student', 'teacherAttendances.teacher'])->find($id);
        return response()->json($attendance);
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $attendance = Attendance::where('id', $id)->first();
            $teacherAttendances = TeacherAttendance::where('attendance_id', $id)->get();
            $studentAttendances = StudentAttendance::where('attendance_id', $id)->get();
            $disciplineRecords = DisciplineRecord::where('attendance_id', $id)->get();
            if ($attendance->delete()) {
                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }

                foreach ($teacherAttendances as $record) {
                    $record->delete();
                }
                foreach ($studentAttendances as $record) {
                    $record->delete();
                }
                foreach ($disciplineRecords as $record) {
                    $record->delete();
                }
                
                AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 0,
                    'action' => 'DELETE_ATTENDANCE',
                    'auditable_type' => Attendance::class,
                    'auditable_id' => $id,
                    'description' => 'Attendace ' . $attendance->date . ' deleted',
                    'old_values' => json_encode($attendance),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success'
                ]);

            }
        }); 
    }


    public function getTeacherAttendances($ay_id)
    {
        $teacher_attendances = Attendance::with(['user', 'teacher', 'subject'])
                                    ->where('academic_year_id', $ay_id)
                                    ->where('module', 'Teacher')
                                    ->get();
        return response()->json($teacher_attendances);
    }

    public function getStudentAttendances($ay_id)
    {
        $student_attendances = Attendance::with(['user', 'teacher', 'subject'])
                                    ->where('academic_year_id', $ay_id)
                                    ->where('module', 'Student')
                                    ->get();
        return response()->json($student_attendances);
    }





}
