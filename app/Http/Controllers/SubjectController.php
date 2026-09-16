<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

use App\Models\Subject;
use App\Models\SubjectPeriod;
use App\Models\StudentSubject;
use App\Models\TeacherSubject;
use App\Models\Submission;
use App\Models\StudentMark;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with('category')->get();
        return response()->json($subjects);
    }
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|max:10',
                'coefficient' => 'required',
                //'periods' => 'required|array',
                'category_id' => 'required'
            ]);
            $subject = new Subject();
            $subject->name = $request->input('name');
            $subject->code = $request->input('code');
            $subject->coefficient = $request->input('coefficient');
            $subject->category_id = $request->input('category_id');
            $subject->save();
            
            $ay_id = $request->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => $request->user() ? $request->user()->id : 0,
                'action' => 'CREATE_SUBJECT',
                'auditable_type' => Subject::class,
                'auditable_id' => $subject->id,
                'description' => 'Subject ' . $subject->name . ' created',
                'old_values' => null,
                'new_values' => json_encode($subject),
                'ip_address' => $request->ip(),
                'academic_year_id' => $ay_id
            ]);

            // NOTE: Periods are managed exclusively via the Timetable sync engine.
            // Direct creation of periods from Subject is disabled.

            return response()->json($subject);
        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|max:10',
                'coefficient' => 'required',
                //'periods' => 'required|array',
                'category_id' => 'required'
            ]);
            $subject = Subject::where('id', $id)->first();
            $old_subject = $subject->replicate();
            $subject->name = $request->input('name');
            $subject->code = $request->input('code');
            $subject->coefficient = $request->input('coefficient');
            $subject->category_id = $request->input('category_id');
            $subject->update();

            $ay_id = $request->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => $request->user() ? $request->user()->id : 0,
                'action' => 'UPDATE_SUBJECT',
                'auditable_type' => Subject::class,
                'auditable_id' => $subject->id,
                'description' => 'Subject ' . $subject->name . ' updated',
                'old_values' => json_encode($old_subject),
                'new_values' => json_encode($subject),
                'ip_address' => $request->ip(),
                'academic_year_id' => $ay_id
            ]);

            // NOTE: Periods are managed exclusively via the Timetable sync engine.
            // Direct creation/update of periods from Subject is disabled.

            return response()->json([
                'status' => 'success',
                'data' => $subject->refresh()
            ]);
        });
        

    }

    public function destroy($id, \Illuminate\Http\Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $submissions = Submission::where('subject_id', $id)->get();
            $marks = StudentMark::where('subject_id', $id)->get();
            if (count($submissions) == 0 && count($marks) == 0) {
                $subject = Subject::where('id', $id)->first();
                $subject_periods = SubjectPeriod::where('subject_id', $id)->get();
                $teacher_subjects = TeacherSubject::where('subject_id', $id)->get();
                $student_subjects = StudentSubject::where('subject_id', $id)->get();
                $subject->delete();
                
                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 0,
                    'action' => 'DELETE_SUBJECT',
                    'auditable_type' => Subject::class,
                    'auditable_id' => $id,
                    'description' => 'Subject ' . $subject->name . ' deleted',
                    'old_values' => json_encode($subject),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);
                foreach ($subject_periods as $subject_period){
                    $subject_period->delete();
                }
                foreach ($teacher_subjects as $teacher_subject) {
                    $teacher_subject->delete();
                }
                foreach ($student_subjects as $student_subject) {
                    $student_subject->delete();
                }
                return response()->json([
                    'status' => 'success'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete this subject'
                ], 522);
            }
        });
        
    }




}
