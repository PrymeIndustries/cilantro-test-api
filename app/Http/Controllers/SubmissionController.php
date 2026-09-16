<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Teacher;
use App\Models\Submission;
use App\Models\StudentMark;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    public function index()
    {
        $submissions = Submission::with(['teacher', 'submission_type'])->get();
        return response()->json($submissions);
    }

    public function getSubmissions($ay_id)
    {
        $submissions = Submission::with(['teacher', 'submission_type'])
                            ->where('academic_year_id', $ay_id)
                            ->get();
        return response()->json($submissions);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $teacher = Teacher::where('id', $request->input('teacher_id'))->first();
            $portal_opened = $this->_submissionPortalStatus();
            if ($portal_opened && $teacher->active) {
                $this->validate($request, [
                    'description' => 'required',
                    'teacher_id' => 'required',
                    'category_id' => 'required',
                    'subject_id' => 'required',
                    'total_marks' => 'required',
                    'submission_type_id' => 'required'
                ]);

                $submission = new Submission();
                $submission->description = $request->input('description');
                $submission->teacher_id = $request->input('teacher_id');
                $submission->category_id = $request->input('category_id');
                $submission->subject_id = $request->input('subject_id');
                $submission->total_marks = $request->input('total_marks');
                $submission->submission_type_id = $request->input('submission_type_id');

                $active_term = Term::where('active', true)->first();
                if (count((array)$active_term) == 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No active term'
                    ], 522);
                }
                $submission->term_id = $active_term->id;

                $active_ay = AcademicYear::where('active', true)->first();
                $submission->academic_year_id = $active_ay->id;

                $submission->save();

                $submission->student_marks()->saveMany(
                    array_map(function ($mark) use ($active_ay, $active_term) {
                        return new StudentMark([
                            'teacher_id' => $mark['teacher_id'],
                            'student_id' => $mark['student_id'],
                            'category_id' => $mark['category_id'],
                            'subject_id' => $mark['subject_id'],
                            'marks_obtained' => $mark['marks_obtained'],
                            'total_marks' => $mark['total_marks'],
                            'submission_type_id' => $mark['submission_type_id'],
                            'term_id' => $active_term->id,
                            'academic_year_id' => $active_ay->id
                        ]);
                    }, $request->marks)
                );

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_SUBMISSION',
                    'auditable_type' => Submission::class,
                    'auditable_id' => $submission->id,
                    'description' => 'Submission ' . $submission->description . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($submission),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $submission->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $submission->refresh()
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
            $teacher = Teacher::where('id', $request->input('teacher_id'))->first();
            $portal_opened = $this->_submissionPortalStatus();
            if ($portal_opened && $teacher->active) {
                $this->validate($request, [
                    'description' => 'required',
                    'teacher_id' => 'required',
                    'category_id' => 'required',
                    'subject_id' => 'required',
                    'total_marks' => 'required',
                    'submission_type_id' => 'required'
                ]);
        
                $submission = Submission::where('id', $id)->first();
                $old_submission = $submission->replicate();
                $submission->description = $request->input('description');
                $submission->teacher_id = $request->input('teacher_id');
                $submission->category_id = $request->input('category_id');
                $submission->subject_id = $request->input('subject_id');
                $submission->total_marks = $request->input('total_marks');
                $submission->submission_type_id = $request->input('submission_type_id');
                $submission->update();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_SUBMISSION',
                    'auditable_type' => Submission::class,
                    'auditable_id' => $submission->id,
                    'description' => 'Submission ' . $submission->description . ' updated',
                    'old_values' => json_encode($old_submission),
                    'new_values' => json_encode($submission),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $submission->academic_year_id
                ]);
        
                return response()->json([
                    'status' => 'success',
                    'data' => $submission->refresh()
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
            $submission = Submission::where('id', $id)->first();
            if ($submission) {
                $submission_student_marks = StudentMark::where('submission_id', $id)->get();
                if ($submission->delete()) {
                    AuditLog::create([
                        'user_id' => request()->user()->id ?? 0,
                        'action' => 'DELETE_SUBMISSION',
                        'auditable_type' => Submission::class,
                        'auditable_id' => $id,
                        'description' => 'Submission ' . $submission->description . ' deleted',
                        'old_values' => json_encode($submission),
                        'new_values' => null,
                        'ip_address' => request()->ip(),
                        'academic_year_id' => $submission->academic_year_id
                    ]);
                    foreach ($submission_student_marks as $submission_student_mark){
                        $submission_student_mark->delete();
                    }
                    return response()->json([
                        'status' => 'success'
                    ]);
                }
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Submission record not found!'
            ], 500);
        });
        
    }

    public function changeStatus($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $submission = Submission::find($id);
            if ($submission) {
                if ($request->status == 'approved'){
                    $submission->approved = 1;
                }
                if ($request->status == 'pending' || $request->status == 'declined'){
                    $submission->approved = 0;
                }
                $submission->status = $request->status;
                $submission->save();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CHANGE_SUBMISSION_STATUS',
                    'auditable_type' => Submission::class,
                    'auditable_id' => $submission->id,
                    'description' => 'Submission ' . $submission->description . ' status changed to ' . $submission->status,
                    'old_values' => json_encode(['status' => $request->status == 'approved' ? 'pending' : 'approved']),
                    'new_values' => json_encode(['status' => $submission->status]),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $submission->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $submission->refresh()
                ]);
                
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Submission record not found!'
                ], 522);
            }
        });
        
    }

    public function changeStatusList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $submission = Submission::find($record['id']);
                if ($submission) {
                    if ($record['status'] == 'approved'){
                        $submission->approved = 1;
                    }
                    if ($record['status'] == 'pending' || $record['status'] == 'declined'){
                        $submission->approved = 0;
                    }
                    $submission->status = $record['status'];
                    $submission->save();

                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CHANGE_SUBMISSION_STATUS_BATCH',
                        'auditable_type' => Submission::class,
                        'auditable_id' => $submission->id,
                        'description' => 'Submission ' . $submission->description . ' status changed to ' . $submission->status . ' in batch',
                        'old_values' => json_encode(['status' => $record['status'] == 'approved' ? 'pending' : 'approved']),
                        'new_values' => json_encode(['status' => $submission->status]),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $submission->academic_year_id
                    ]);
                }
            }
            
            return response()->json([
                'status' => 'success'
            ]);
        });
        
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
