<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Term;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TermController extends Controller
{
    public function index()
    {
        $terms = Term::all();
        return response()->json($terms);
    }

    public function changeActiveStatus($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'active' => 'required'
            ]);
            $term = Term::find($id);
            if ($term) {
                $old_term = $term->replicate();
                $term->active = $request->input('active');
                $term->update();

                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 0,
                    'action' => 'UPDATE_TERM_STATUS',
                    'auditable_type' => Term::class,
                    'auditable_id' => $term->id,
                    'description' => 'Term ' . $term->name . ' active status changed to ' . ($term->active ? 'Active' : 'Inactive'),
                    'old_values' => json_encode($old_term),
                    'new_values' => json_encode($term),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $term->refresh()
                ]);
                
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Term not found!'
                ]);
            }
        });
        
    }

    public function changeActiveStatusList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $errors = [];
                if (!isset($record['active'])) {
                    $errors[] = 'Active field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }
                $term = Term::find($record['id']);
                if ($term) {
                    $old_term = $term->replicate();
                    $term->active = $record['active'];
                    $term->update();

                    $ay_id = $request->input('academic_year_id');
                    if (!$ay_id) {
                        $ay = AcademicYear::where('active', true)->first();
                        $ay_id = $ay ? $ay->id : 0;
                    }
                    AuditLog::create([
                        'user_id' => $request->user() ? $request->user()->id : 0,
                        'action' => 'UPDATE_TERM_STATUS',
                        'auditable_type' => Term::class,
                        'auditable_id' => $term->id,
                        'description' => 'Term ' . $term->name . ' active status changed to ' . ($term->active ? 'Active' : 'Inactive') . ' in batch',
                        'old_values' => json_encode($old_term),
                        'new_values' => json_encode($term),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $ay_id
                    ]);
                }
            }
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }



}
