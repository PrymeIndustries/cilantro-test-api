<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\StudentSubject;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentSubjectController extends Controller
{
    public function index()
    {
        $student_subjects = StudentSubject::with('subject')->get();
        return response()->json($student_subjects);
    }

    public function getCurrentAYStudentSubjects()
    {
        $active_ay = AcademicYear::where('active', true)->first();
        $student_subjects = StudentSubject::with('subject')
                    ->where('academic_year_id', $active_ay->id)
                    ->get();
        return response()->json($student_subjects);
    }

    public function getStudentSubjects($ay_id)
    {
        $student_subjects = StudentSubject::with('subject')
                        ->where('academic_year_id', $ay_id)
                        ->get();
        return response()->json($student_subjects);
    }

    public function getSingleStudentSubjects($id)
    {
        $student_subjects = StudentSubject::with('subject')
                        ->where('student_id', $id)
                        ->get();
        return response()->json($student_subjects);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'student_id' => 'required',
                'subject_id' => 'required',
            ]);
            $student_subject = new StudentSubject();
            $student_subject->student_id = $request->input('student_id');
            $student_subject->subject_id = $request->input('subject_id');

            $active_ay = AcademicYear::where('active', true)->first();
            $student_subject->academic_year_id = $active_ay->id;

            if ($student_subject->save()) {
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'ASSIGN_STUDENT_SUBJECT',
                    'auditable_type' => StudentSubject::class,
                    'auditable_id' => $student_subject->id,
                    'description' => 'Subject assigned to student ' . $student_subject->student_id,
                    'old_values' => null,
                    'new_values' => json_encode($student_subject),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $student_subject->academic_year_id
                ]);

                return response()->json($student_subject);
            }
        });
        
    }

    public function storeList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record){
                $errors = [];
                if (!isset($record['student_id'])) {
                    $errors[] = 'Student id field is required';
                }
                if (!isset($record['subject_id'])) {
                    $errors[] = 'Subject id field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }
                $student_subject = new StudentSubject();
                $student_subject->student_id = $record['student_id'];
                $student_subject->subject_id = $record['subject_id'];

                $active_ay = AcademicYear::where('active', true)->first();
                $student_subject->academic_year_id = $active_ay->id;
                
                if ($student_subject->save()) {
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'ASSIGN_STUDENT_SUBJECT_BATCH',
                        'auditable_type' => StudentSubject::class,
                        'auditable_id' => $student_subject->id,
                        'description' => 'Subject assigned to student ' . $student_subject->student_id . ' in batch',
                        'old_values' => null,
                        'new_values' => json_encode($student_subject),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $student_subject->academic_year_id
                    ]);
                }
            }
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'student_id' => 'required',
                'subject_id' => 'required',
            ]);
            $student_subject = StudentSubject::where('id', $id)->first();
            if ($student_subject) {
                $old_ss = $student_subject->replicate();
                $student_subject->student_id = $request->input('student_id');
                $student_subject->subject_id = $request->input('subject_id');
                if ($student_subject->update()) {
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'UPDATE_STUDENT_SUBJECT_ASSIGNMENT',
                        'auditable_type' => StudentSubject::class,
                        'auditable_id' => $student_subject->id,
                        'description' => 'Student subject assignment updated',
                        'old_values' => json_encode($old_ss),
                        'new_values' => json_encode($student_subject),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $student_subject->academic_year_id
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $student_subject->refresh()
                    ]);
                }
            }
        });
        

    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $student_subject = StudentSubject::where('id', $id)->first();
            if ($student_subject && $student_subject->delete()) {
                AuditLog::create([
                    'user_id' => request()->user()->id ?? 0,
                    'action' => 'REMOVE_STUDENT_SUBJECT_ASSIGNMENT',
                    'auditable_type' => StudentSubject::class,
                    'auditable_id' => $id,
                    'description' => 'Subject assignment removed for student ' . $student_subject->student_id,
                    'old_values' => json_encode($student_subject),
                    'new_values' => null,
                    'ip_address' => request()->ip(),
                    'academic_year_id' => $student_subject->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
            }
        });
        
    }



}
