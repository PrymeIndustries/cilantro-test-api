<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Result;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    public function index()
    {
        $results = Result::with('student')->get();
        return response()->json($results);
    }

    public function getResults($ay_id)
    {
        $results = Result::with('student')
                        ->where('academic_year_id', $ay_id)
                        ->get();
        return response()->json($results);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'student_id' => 'required',
                'category_id' => 'required',
                'marks_obtained' => 'required',
                'total_marks' => 'required',
                'class_rank' => 'required',
                'overall_rank' => 'required',
                'average' => 'required',
                'promoted' => 'required',
                'decision' => 'required'
            ]);

            $result = new Result();
            $result->student_id = $request->input('student_id');
            $result->category_id = $request->input('category_id');
            $result->marks_obtained = $request->input('marks_obtained');
            $result->marks_obtained_ptg = $request->input('marks_obtained_ptg');
            $result->total_marks = $request->input('total_marks');
            $result->class_rank = $request->input('class_rank');
            $result->overall_rank = $request->input('overall_rank');
            $result->average = $request->input('average');
            $result->promoted = $request->input('promoted');
            $result->decision = $request->input('decision');
            $result->remark = $request->input('remark');

            $active_term = Term::where('active', true)->first();
            if (count((array)$active_term) == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active term'
                ], 522);
            }
            $result->term_id = $active_term->id;

            $active_ay = AcademicYear::where('active', true)->first();
            $result->academic_year_id = $active_ay->id;

            $result->save();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'CREATE_RESULT',
                'auditable_type' => Result::class,
                'auditable_id' => $result->id,
                'description' => 'Result for student ID ' . $result->student_id . ' created',
                'old_values' => null,
                'new_values' => json_encode($result),
                'ip_address' => $request->ip(),
                'academic_year_id' => $result->academic_year_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $result->refresh()
            ]);
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
                if (!isset($record['category_id'])) {
                    $errors[] = 'Category id field is required';
                }
                if (!isset($record['marks_obtained'])) {
                    $errors[] = 'Marks obtained field is required';
                }
                if (!isset($record['total_marks'])) {
                    $errors[] = 'Total marks field is required';
                }
                if (!isset($record['class_rank'])) {
                    $errors[] = 'Class rank field is required';
                }
                if (!isset($record['overall_rank'])) {
                    $errors[] = 'Overall rank field is required';
                }
                if (!isset($record['average'])) {
                    $errors[] = 'Average field is required';
                }
                if (!isset($record['promoted'])) {
                    $errors[] = 'Promoted field is required';
                }
                if (!isset($record['decision'])) {
                    $errors[] = 'Decision field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }
                $result = new Result();
                $result->student_id = $record['student_id'];
                $result->category_id = $record['category_id'];
                $result->marks_obtained = $record['marks_obtained'];
                $result->marks_obtained_ptg = $record['marks_obtained_ptg'];
                $result->total_marks = $record['total_marks'];
                $result->class_rank = $record['class_rank'];
                $result->overall_rank = $record['overall_rank'];
                $result->average = $record['average'];
                $result->promoted = $record['promoted'];
                $result->decision = $record['decision'];
                $result->remark = $record['remark'];
                $active_term = Term::where('active', true)->first();
                if (count((array)$active_term) == 0){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No active term'
                    ], 522);
                }
                $result->term_id = $active_term->id;
                $active_ay = AcademicYear::where('active', true)->first();
                $result->academic_year_id = $active_ay->id;
                $result->save();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_RESULT_BATCH',
                    'auditable_type' => Result::class,
                    'auditable_id' => $result->id,
                    'description' => 'Result for student ID ' . $result->student_id . ' created in batch',
                    'old_values' => null,
                    'new_values' => json_encode($result),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $result->academic_year_id
                ]);
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
                'category_id' => 'required',
                'marks_obtained' => 'required',
                'total_marks' => 'required',
                'average' => 'required',
                'class_rank' => 'required',
                'overall_rank' => 'required',
                'promoted' => 'required',
                'decision' => 'required',
                'passed_examination' => 'required',
                'published' => 'required'
            ]);
            $result = Result::where('id', $id)->first();
            $old_result = $result->replicate();
            $result->student_id = $request->input('student_id');
            $result->category_id = $request->input('category_id');
            $result->marks_obtained = $request->input('marks_obtained');
            $result->marks_obtained_ptg = $request->input('marks_obtained_ptg');
            $result->total_marks = $request->input('total_marks');
            $result->class_rank = $request->input('class_rank');
            $result->overall_rank = $request->input('overall_rank');
            $result->average = $request->input('average');
            $result->promoted = $request->input('promoted');
            $result->decision = $request->input('decision');
            $result->remark = $request->input('remark');
            $result->passed_examination = $request->input('passed_examination');
            $result->published = $request->input('published');
            $result->update();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'UPDATE_RESULT',
                'auditable_type' => Result::class,
                'auditable_id' => $result->id,
                'description' => 'Result for student ID ' . $result->student_id . ' updated',
                'old_values' => json_encode($old_result),
                'new_values' => json_encode($result),
                'ip_address' => $request->ip(),
                'academic_year_id' => $result->academic_year_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $result->refresh()
            ]);
        });
        

    }

    public function updateList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record){
                $errors = [];
                if (!isset($record['passed_examination'])) {
                    $errors[] = 'Passed examination field is required';
                }
                if (!isset($record['published'])) {
                    $errors[] = 'Published field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }
                $result = Result::find($record['id']);
                $old_result = $result->replicate();
                $result->passed_examination = $record['passed_examination'];
                $result->published = $record['published'];
                $result->save();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_RESULT_BATCH',
                    'auditable_type' => Result::class,
                    'auditable_id' => $result->id,
                    'description' => 'Result for student ID ' . $result->student_id . ' updated in batch',
                    'old_values' => json_encode($old_result),
                    'new_values' => json_encode($result),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $result->academic_year_id
                ]);
            }
            
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $result = Result::where('id', $id)->first();
            $result->delete();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'DELETE_RESULT',
                'auditable_type' => Result::class,
                'auditable_id' => $id,
                'description' => 'Result for student ID ' . $result->student_id . ' deleted',
                'old_values' => json_encode($result),
                'new_values' => null,
                'ip_address' => $request->ip(),
                'academic_year_id' => $result->academic_year_id
            ]);

            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function destroyList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record){
                $result = Result::find($record['id']);
                $result->delete();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'DELETE_RESULT_BATCH',
                    'auditable_type' => Result::class,
                    'auditable_id' => $record['id'],
                    'description' => 'Result for student ID ' . $result->student_id . ' deleted in batch',
                    'old_values' => json_encode($result),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $result->academic_year_id
                ]);
            }
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }




}
