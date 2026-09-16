<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\SubmissionType;
use App\Models\AuditLog;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionTypeController extends Controller
{
    public function index()
    {
        $submission_types = SubmissionType::all();
        return response()->json($submission_types);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|max:10',
                'description' => 'required',
                'weight_percentage' => 'required',
                'category_id' => 'required',
            ]);
            $submission_type = new SubmissionType();
            $submission_type->name = $request->input('name');
            $submission_type->code = $request->input('code');
            $submission_type->description = $request->input('description');
            $submission_type->weight_percentage = $request->input('weight_percentage');
            $submission_type->category_id = $request->input('category_id');
            if ($submission_type->save()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_SUBMISSION_TYPE',
                    'auditable_type' => SubmissionType::class,
                    'auditable_id' => $submission_type->id,
                    'description' => 'Submission type ' . $submission_type->name . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($submission_type),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json($submission_type);
            }
        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|max:10',
                'description' => 'required',
                'weight_percentage' => 'required',
                'category_id' => 'required',
            ]);
            $submission_type = SubmissionType::where('id', $id)->first();
            if ($submission_type) {
                $old_st = $submission_type->replicate();
                $submission_type->name = $request->input('name');
                $submission_type->code = $request->input('code');
                $submission_type->description = $request->input('description');
                $submission_type->weight_percentage = $request->input('weight_percentage');
                $submission_type->category_id = $request->input('category_id');
                if ($submission_type->update()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'UPDATE_SUBMISSION_TYPE',
                        'auditable_type' => SubmissionType::class,
                        'auditable_id' => $submission_type->id,
                        'description' => 'Submission type ' . $submission_type->name . ' updated',
                        'old_values' => json_encode($old_st),
                        'new_values' => json_encode($submission_type),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $submission_type->refresh()
                    ]);
                }
            }
        });
        

    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $submissions = Submission::where('submission_type_id', $id)->get();
            if (count($submissions) == 0) {
                $submission_type = SubmissionType::where('id', $id)->first();
                if ($submission_type && $submission_type->delete()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => request()->user()->id ?? 0,
                        'action' => 'DELETE_SUBMISSION_TYPE',
                        'auditable_type' => SubmissionType::class,
                        'auditable_id' => $id,
                        'description' => 'Submission type ' . $submission_type->name . ' deleted',
                        'old_values' => json_encode($submission_type),
                        'new_values' => null,
                        'ip_address' => request()->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);

                    return response()->json([
                        'status' => 'success'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete this submission type'
                ], 522);
            }
        });
        
    }

    public function changeStatus($id)
    {
        return DB::transaction(function () use ($id) {
            $submission_type = SubmissionType::find($id);
            if ($submission_type) {
                $old_st = $submission_type->replicate();
                $submission_type->active = !$submission_type->active;
                if ($submission_type->save()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => request()->user()->id ?? 0,
                        'action' => 'CHANGE_SUBMISSION_TYPE_STATUS',
                        'auditable_type' => SubmissionType::class,
                        'auditable_id' => $submission_type->id,
                        'description' => 'Submission type ' . $submission_type->name . ' status changed',
                        'old_values' => json_encode($old_st),
                        'new_values' => json_encode($submission_type),
                        'ip_address' => request()->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $submission_type->refresh()
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Submission type record not found!'
                ], 522);
            }
        });
        
    }


}
