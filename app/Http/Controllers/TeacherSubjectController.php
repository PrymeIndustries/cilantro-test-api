<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\TeacherSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherSubjectController extends Controller
{
    public function index()
    {
        $teacher_subjects = TeacherSubject::with('subject')->get();
        return response()->json($teacher_subjects);
    }

    public function getCurrentAYTeacherSubjects()
    {
        $active_ay = AcademicYear::where('active', true)->first();
        $teacher_subjects = TeacherSubject::with('subject')
                    ->where('academic_year_id', $active_ay->id)
                    ->get();
        return response()->json($teacher_subjects);
    }

    public function getTeacherSubjects($ay_id)
    {
        $teacher_subjects = TeacherSubject::with('subject')
                        ->where('academic_year_id', $ay_id)
                        ->get();
        return response()->json($teacher_subjects);
    }

    public function getSingleTeacherSubjects($id)
    {
        $teacher_subjects = TeacherSubject::with('subject')
                        ->where('teacher_id', $id)
                        ->get();
        return response()->json($teacher_subjects);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'teacher_id' => 'required',
                'subject_id' => 'required',
            ]);

            $teacher_subject = new TeacherSubject();
            $teacher_subject->teacher_id = $request->input('teacher_id');
            $teacher_subject->subject_id = $request->input('subject_id');

            $active_ay = AcademicYear::where('active', true)->first();
            $teacher_subject->academic_year_id = $active_ay->id;

            if ($teacher_subject->save()) {
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_TEACHER_SUBJECT',
                    'auditable_type' => TeacherSubject::class,
                    'auditable_id' => $teacher_subject->id,
                    'description' => 'Teacher assigned to subject',
                    'old_values' => null,
                    'new_values' => json_encode($teacher_subject),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay->id
                ]);

                return response()->json($teacher_subject);
            }
        });
        
    }

    public function storeList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record){
                $errors = [];
                if (!isset($record['teacher_id'])) {
                    $errors[] = 'Teacher id field is required';
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
                $teacher_subject = new TeacherSubject();
                $teacher_subject->teacher_id = $record['teacher_id'];
                $teacher_subject->subject_id = $record['subject_id'];

                $active_ay = AcademicYear::where('active', true)->first();
                $teacher_subject->academic_year_id = $active_ay->id;

                if ($teacher_subject->save()) {
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CREATE_TEACHER_SUBJECT_BATCH',
                        'auditable_type' => TeacherSubject::class,
                        'auditable_id' => $teacher_subject->id,
                        'description' => 'Teacher assigned to subject in batch',
                        'old_values' => null,
                        'new_values' => json_encode($teacher_subject),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay->id
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
                'teacher_id' => 'required',
                'subject_id' => 'required',
            ]);
            $teacher_subject = TeacherSubject::where('id', $id)->first();
            $old_teacher_subject = $teacher_subject->replicate();
            $teacher_subject->teacher_id = $request->input('teacher_id');
            $teacher_subject->subject_id = $request->input('subject_id');
            if ($teacher_subject->update()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_TEACHER_SUBJECT',
                    'auditable_type' => TeacherSubject::class,
                    'auditable_id' => $teacher_subject->id,
                    'description' => 'Teacher subject modified',
                    'old_values' => json_encode($old_teacher_subject),
                    'new_values' => json_encode($teacher_subject),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $teacher_subject->refresh()
                ]);
            }
        });
        

    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $teacher_subject = TeacherSubject::where('id', $id)->first();
            if ($teacher_subject->delete()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'DELETE_TEACHER_SUBJECT',
                    'auditable_type' => TeacherSubject::class,
                    'auditable_id' => $id,
                    'description' => 'Teacher subject assigned deleted',
                    'old_values' => json_encode($teacher_subject),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
            }
        });
        
    }



}
