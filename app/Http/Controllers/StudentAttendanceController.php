<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Attendance;
use App\Models\StudentAttendance;
use App\Models\Student;
use App\Models\DisciplineRecord;
use App\Models\AuditLog;
use App\Packages\JsonStorage\JsonStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentAttendanceController extends Controller
{
    public function index()
    {
        $student_attendances = StudentAttendance::with('attendance')->get();
        return response()->json($student_attendances);
    }

    public function getAttendances($ay_id)
    {
        $student_attendances = StudentAttendance::with('attendance')
                                    ->where('academic_year_id', $ay_id)->get();
        return response()->json($student_attendances);
    }

    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'teacher_id' => 'nullable|exists:teachers,id',
            'user_id' => 'nullable|exists:users,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'date' => 'required|date',
            'students' => 'required|array',
            'students.*.student_id' => 'required|exists:students,id',
            'students.*.present' => 'required|boolean',
            'students.*.hours' => 'required_if:students.*.present,false|numeric|min:1',
        ]);

        try {
            DB::beginTransaction();

            $store = new JsonStorage();
            $sc = $store->get('school', false);

            $active_ay = AcademicYear::where('active', true)->first();

            $active_term = Term::where('active', true)->first();
            if (count((array)$active_term) == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active term'
                ], 522);
            }

            $attendance = Attendance::create([
                'date' => $validated['date'],
                'subject_id' => $validated['subject_id'] ?? null,
                'teacher_id' => $validated['teacher_id'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'module' => 'Student',
                'academic_year_id' => $active_ay->id
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'CREATE_ATTENDANCE',
                'auditable_type' => Attendance::class,
                'auditable_id' => $attendance->id,
                'description' => $validated['teacher_id'] != null ? 'Attendance record created by teacher ID ' . $validated['teacher_id'] . ' on ' . $validated['date'] : 'Attendance record created by user ID ' . ($request->user()->id ?? 0) . ' on ' . $validated['date'],
                'old_values' => null,
                'new_values' => json_encode($attendance),
                'ip_address' => $request->ip(),
                'academic_year_id' => $active_ay->id,
            ]);

            foreach ($validated['students'] as $studentData) {
                $studentAttendance = StudentAttendance::create([
                    'attendance_id' => $attendance->id,
                    'student_id' => $studentData['student_id'],
                    'present' => $studentData['present'],
                    'term_id' => $active_term->id,
                    'academic_year_id' => $active_ay->id
                ]);

                if (!$studentData['present']) {
                    $student = Student::find($studentData['student_id']);

                    $disciplineRecord = DisciplineRecord::create([
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id,
                        'note' => 'Absent from class [Teacher]',
                        'hours_of_punishment' => 0,
                        'number_of_warnings' => 0,
                        'days_of_suspension' => 0,
                        'hours_of_absences' => $studentData['hours'],
                        'term_id' => $active_term->id,
                        'academic_year_id' => $active_ay->id
                    ]);

                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CREATE_DISCIPLINE_RECORD_ABSENCE',
                        'auditable_type' => DisciplineRecord::class,
                        'auditable_id' => $disciplineRecord->id,
                        'description' => 'Discipline record created for absent student ID ' . $student->id . ' (' . $studentData['hours'] . ' hour(s))',
                        'old_values' => null,
                        'new_values' => json_encode($disciplineRecord),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Attendance recorded successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error recording attendance.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
