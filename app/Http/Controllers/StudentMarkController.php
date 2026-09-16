<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Teacher;
use App\Models\StudentMark;
use App\Models\Setting;
use App\Models\Term;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentMarkController extends Controller
{
    public function index()
    {
        $marks = StudentMark::with(['student', 'subject', 'submission', 'submission_type'])->get();
        return response()->json($marks);
    }

    public function getMarks($ay_id)
    {
        $marks = StudentMark::with(['student', 'subject', 'submission', 'submission_type'])
                    ->where('academic_year_id', $ay_id)
                    ->get();
        return response()->json($marks);
    }

    public function getSingleStudentMarks($id)
    {
        $marks = StudentMark::with(['student', 'subject', 'submission', 'submission_type'])
                        ->where('student_id', $id)
                        ->get();
        return response()->json($marks);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $teacher = Teacher::where('id', $request->input('teacher_id'))->first();
            $portal_opened = $this->_submissionPortalStatus();
            if ($portal_opened && $teacher->active) {
                $this->validate($request, [
                    'submission_id' => 'required',
                    'teacher_id' => 'required',
                    'student_id' => 'required',
                    'category_id' => 'required',
                    'subject_id' => 'required',
                    'marks_obtained' => 'required',
                    'total_marks' => 'required',
                    'submission_type_id' => 'required'
                ]);

                $student_mark = new StudentMark();
                $student_mark->submission_id = $request->input('submission_id');
                $student_mark->teacher_id = $request->input('teacher_id');
                $student_mark->student_id = $request->input('student_id');
                $student_mark->category_id = $request->input('category_id');
                $student_mark->subject_id = $request->input('subject_id');
                $student_mark->marks_obtained = $request->input('marks_obtained');
                $student_mark->total_marks = $request->input('total_marks');
                $student_mark->submission_type_id = $request->input('submission_type_id');

                $active_term = Term::where('active', true)->first();
                if (count((array)$active_term) == 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No active term'
                    ], 522);
                }
                $student_mark->term_id = $active_term->id;

                $active_ay = AcademicYear::where('active', true)->first();
                $student_mark->academic_year_id = $active_ay->id;

                $student_mark->save();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_STUDENT_MARK',
                    'auditable_type' => StudentMark::class,
                    'auditable_id' => $student_mark->id,
                    'description' => 'Mark for student ID ' . $student_mark->student_id . ' created for submission ID ' . $student_mark->submission_id,
                    'old_values' => null,
                    'new_values' => json_encode($student_mark),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $student_mark->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $student_mark->refresh()
                ]);

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Submission portal is not open'
                ], 522);
            }
        });
        

    }

    public function storeList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $teacher = Teacher::where('id', $request->input('teacher_id'))->first();
            $portal_opened = $this->_submissionPortalStatus();
            if ($portal_opened && $teacher->active) {
                $records = $request->records;
                foreach ($records as $record) {
                    $errors = [];
                    if (!isset($record['submission_id'])) {
                        $errors[] = 'Submission id field is required';
                    }
                    if (!isset($record['teacher_id'])) {
                        $errors[] = 'Teacher id field is required';
                    }
                    if (!isset($record['student_id'])) {
                        $errors[] = 'Student id field is required';
                    }
                    if (!isset($record['category_id'])) {
                        $errors[] = 'Category id field is required';
                    }
                    if (!isset($record['subject_id'])) {
                        $errors[] = 'Subject id field is required';
                    }
                    if (!isset($record['marks_obtained'])) {
                        $errors[] = 'Marks obtained field is required';
                    }
                    if (!isset($record['total_marks'])) {
                        $errors[] = 'Total marks field is required';
                    }
                    if (!isset($record['submission_type_id'])) {
                        $errors[] = 'Submission type id field is required';
                    }
                    //
                    if (count($errors) > 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $errors[count($errors) - 1]
                        ], 522);
                    }
                    $student_mark = new StudentMark();
                    $student_mark->submission_id = $record['submission_id'];
                    $student_mark->teacher_id = $record['teacher_id'];
                    $student_mark->student_id = $record['student_id'];
                    $student_mark->category_id = $record['category_id'];
                    $student_mark->subject_id = $record['subject_id'];
                    $student_mark->marks_obtained = $record['marks_obtained'];
                    $student_mark->total_marks = $record['total_marks'];
                    $student_mark->submission_type_id = $record['submission_type_id'];
                    $active_term = Term::where('active', true)->first();
                    if (count((array)$active_term) == 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No active term'
                        ], 522);
                    }
                    $student_mark->term_id = $active_term->id;
                    $active_ay = AcademicYear::where('active', true)->first();
                    $student_mark->academic_year_id = $active_ay->id;
                    $student_mark->save();

                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CREATE_STUDENT_MARK_BATCH',
                        'auditable_type' => StudentMark::class,
                        'auditable_id' => $student_mark->id,
                        'description' => 'Mark for student ID ' . $student_mark->student_id . ' created in batch',
                        'old_values' => null,
                        'new_values' => json_encode($student_mark),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $student_mark->academic_year_id
                    ]);
                }

                return response()->json([
                    'status' => 'success'
                ]);

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Submission portal is not open'
                ], 522);
            } 
        });
         
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $portal_opened = $this->_submissionPortalStatus();
            if ($portal_opened) {
                $this->validate($request, [
                    'submission_id' => 'required',
                    'teacher_id' => 'required',
                    'student_id' => 'required',
                    'category_id' => 'required',
                    'subject_id' => 'required',
                    'marks_obtained' => 'required',
                    'total_marks' => 'required',
                    'submission_type_id' => 'required'
                ]);
        
                $student_mark = StudentMark::where('id', $id)->first();
                $old_mark = $student_mark->replicate();
                $student_mark->submission_id = $request->input('submission_id');
                $student_mark->teacher_id = $request->input('teacher_id');
                $student_mark->student_id = $request->input('student_id');
                $student_mark->category_id = $request->input('category_id');
                $student_mark->subject_id = $request->input('subject_id');
                $student_mark->marks_obtained = $request->input('marks_obtained');
                $student_mark->total_marks = $request->input('total_marks');
                $student_mark->submission_type_id = $request->input('submission_type_id');
                $student_mark->update();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_STUDENT_MARK',
                    'auditable_type' => StudentMark::class,
                    'auditable_id' => $student_mark->id,
                    'description' => 'Mark for student ID ' . $student_mark->student_id . ' updated',
                    'old_values' => json_encode($old_mark),
                    'new_values' => json_encode($student_mark),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $student_mark->academic_year_id
                ]);
        
                return response()->json([
                    'status' => 'success',
                    'data' => $student_mark->refresh()
                ]);

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Submission portal is not open'
                ], 522);
            }
        });
        
    }

    public function updateList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $portal_opened = $this->_submissionPortalStatus();
            if ($portal_opened) {
                $records = $request->records;
                foreach ($records as $record) {
                    $errors = [];
                    if (!isset($record['submission_id'])) {
                        $errors[] = 'Submission id field is required';
                    }
                    if (!isset($record['teacher_id'])) {
                        $errors[] = 'Teacher id field is required';
                    }
                    if (!isset($record['student_id'])) {
                        $errors[] = 'Student id field is required';
                    }
                    if (!isset($record['category_id'])) {
                        $errors[] = 'Category id field is required';
                    }
                    if (!isset($record['subject_id'])) {
                        $errors[] = 'Subject id field is required';
                    }
                    if (!isset($record['marks_obtained'])) {
                        $errors[] = 'Marks obtained field is required';
                    }
                    if (!isset($record['total_marks'])) {
                        $errors[] = 'Total marks field is required';
                    }
                    if (!isset($record['submission_type_id'])) {
                        $errors[] = 'Submission type id field is required';
                    }
                    //
                    if (count($errors) > 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $errors[count($errors) - 1]
                        ], 522);
                    }
                    $student_mark = StudentMark::find($record['id']);
                    $old_mark = $student_mark->replicate();
                    $student_mark->submission_id = $record['submission_id'];
                    $student_mark->teacher_id = $record['teacher_id'];
                    $student_mark->student_id = $record['student_id'];
                    $student_mark->category_id = $record['category_id'];
                    $student_mark->subject_id = $record['subject_id'];
                    $student_mark->marks_obtained = $record['marks_obtained'];
                    $student_mark->total_marks = $record['total_marks'];
                    $student_mark->submission_type_id = $record['submission_type_id'];
                    $student_mark->save();

                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'UPDATE_STUDENT_MARK_BATCH',
                        'auditable_type' => StudentMark::class,
                        'auditable_id' => $student_mark->id,
                        'description' => 'Mark for student ID ' . $student_mark->student_id . ' updated in batch',
                        'old_values' => json_encode($old_mark),
                        'new_values' => json_encode($student_mark),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $student_mark->academic_year_id
                    ]);
                }
                
                return response()->json([
                    'status' => 'success'
                ]);

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Submission portal is not open'
                ], 522);
            } 
        });
         
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $student_mark = StudentMark::where('id', $id)->first();
            $student_mark->delete();

            AuditLog::create([
                'user_id' => request()->user()->id ?? 0,
                'action' => 'DELETE_STUDENT_MARK',
                'auditable_type' => StudentMark::class,
                'auditable_id' => $id,
                'description' => 'Mark for student ID ' . $student_mark->student_id . ' deleted',
                'old_values' => json_encode($student_mark),
                'new_values' => null,
                'ip_address' => request()->ip(),
                'academic_year_id' => $student_mark->academic_year_id
            ]);

            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function getStudentMarks($student_id, Request $request)
    {
        $data = StudentMark::with(['student', 'subject', 'submission', 'submission_type'])
                    ->where('academic_year_id', $request->academic_year_id)
                    ->where('term_id', $request->term_id)
                    ->get();
        $student_marks = [];
        if (count($data) > 0) {
            foreach ($data as $mark) {
                if ($mark->student_id == $student_id && $mark->submission['approved']) {
                    $student_marks[] = $mark;
                }
            }
            if (count($student_marks) > 0) {
                return response()->json($student_marks);
            } else {
                return response()->json([
                    'status' => 'error 1',
                    'message' => 'Student marks not found'
                ], 404);
            }
        } else {
            return response()->json([
                'status' => 'error 2',
                'message' => 'No marks'
            ], 404);
        }
    }



    public function _submissionPortalStatus() {
        $setting = Setting::where('slug', 'submission')->first();
        if ($setting) {
            $portal_opened = 0;
            foreach ($setting['parameters'] as $param) {
                if ($param['slug'] == 'open-submission-portal') {
                    $portal_opened = $param['enabled'];
                    break;
                }
            }
            if ($portal_opened) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }




}
