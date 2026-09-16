<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\TeacherAttendance;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherAttendanceController extends Controller
{
    public function index()
    {
        $teacher_attendances = TeacherAttendance::with('attendance')->get();
        return response()->json($teacher_attendances);
    }

    public function getAttendances($ay_id)
    {
        $teacher_attendances = TeacherAttendance::with('attendance')
                                    ->where('academic_year_id', $ay_id)->get();
        return response()->json($teacher_attendances);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            
            $records = $request->records;

            if (count($records) == 0 || !$records[0]) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No records'
                ], 522);
            }

            $active_ay = AcademicYear::where('active', true)->first();

            $attendance = Attendance::create([
                'date' =>  $records[0]['date'],
                'subject_id' => null,
                'teacher_id' => null,
                'user_id' => $records[0]['user_id'] ?? null,
                'module' => 'Teacher',
                'academic_year_id' => $active_ay->id
            ]);

            foreach ($records as $record) {
                $errors = [];
                if (!isset($record['teacher_id'])) {
                    $errors[] = 'Teacher id field is required';
                }
                if (!isset($record['date'])) {
                    $errors[] = 'Date field is required';
                }
                if (!isset($record['present'])) {
                    $errors[] = 'Present field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }

                $teacher_attendance = new TeacherAttendance();
                $teacher_attendance->attendance_id = $attendance->id;
                $teacher_attendance->teacher_id = $record['teacher_id'];
                $teacher_attendance->present = $record['present'];
                $teacher_attendance->academic_year_id = $active_ay->id;

                if ($teacher_attendance->save()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CREATE_TEACHER_ATTENDANCE_BATCH',
                        'auditable_type' => TeacherAttendance::class,
                        'auditable_id' => $teacher_attendance->id,
                        'description' => 'Attendance marked as ' . ($teacher_attendance->present ? 'Present' : 'Absent') . ' for teacher ID ' . $teacher_attendance->teacher_id . ' on ' . $attendance->date . ', in batch',
                        'old_values' => null,
                        'new_values' => json_encode($teacher_attendance),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay->id,
                    ]);
                }

            }
            
            return response()->json([
                'status' => 'success'
            ]);

        });
        
    }


}
