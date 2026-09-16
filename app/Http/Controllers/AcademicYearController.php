<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function index()
    {
        $years = AcademicYear::all();
        return response()->json($years);
    }

    public function setGeneralConfStatus($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $year = AcademicYear::find($id);

            if ($year) {
                $old_year = clone $year;
                $year->status = $request->input('status');
                $year->active = $request->input('active');
                $year->temp_active = $request->input('temp_active');
                
                if ($year->update()) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'UPDATE_ACADEMIC_YEAR_CONFIGURATION',
                        'auditable_type' => AcademicYear::class,
                        'auditable_id' => $year->id,
                        'description' => 'Academic year ' . $year->year . ' configuration updated',
                        'old_values' => json_encode($old_year),
                        'new_values' => json_encode($year),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $ay_id
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $year->refresh()
                    ]);
                }

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Year not found!'
                ]);
            }
        });
        
    }

    public function setGeneralConfStatusList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $year = AcademicYear::find($record['id']);
                if ($year) {
                    $old_year = clone $year;
                    $year->status = $record['status'];
                    $year->active = $record['active'];
                    $year->temp_active = $record['temp_active'];
                    
                    if ($year->update()) {
                        $ay = AcademicYear::where('active', true)->first();
                        $ay_id = $ay ? $ay->id : 0;
                        AuditLog::create([
                            'user_id' => $request->user()->id ?? 0,
                            'action' => 'UPDATE_ACADEMIC_YEAR_CONFIGURATION_BATCH',
                            'auditable_type' => AcademicYear::class,
                            'auditable_id' => $year->id,
                            'description' => 'Academic year ' . $year->year . ' configuration updated in batch',
                            'old_values' => json_encode($old_year),
                            'new_values' => json_encode($year),
                            'ip_address' => $request->ip(),
                            'academic_year_id' => $ay_id
                        ]);
                    }
                }

            }

            return response()->json([
                'status' => 'success'
            ]);

        });
        
    }

    public function changeTempActiveStatus($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'temp_active' => 'required'
            ]);
            $year = AcademicYear::find($id);
            if ($year) {
                $old_year = clone $year;
                $year->temp_active = $request->input('temp_active');
                
                if ($year->update()) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'UPDATE_ACADEMIC_YEAR_TEMP_ACTIVE_STATUS',
                        'auditable_type' => AcademicYear::class,
                        'auditable_id' => $year->id,
                        'description' => 'Academic year ' . $year->year . ' temp active status changed to ' . ($year->temp_active ? 'True' : 'False'),
                        'old_values' => json_encode($old_year),
                        'new_values' => json_encode($year),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $ay_id
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $year->refresh()
                    ]);

                }

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Year not found!'
                ]);
            }
        });
        
    }

    public function changeTempActiveStatusList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $errors = [];
                if (!isset($record['temp_active'])) {
                    $errors[] = 'Temp active field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }
                $year = AcademicYear::find($record['id']);
                if ($year) {
                    $old_year = clone $year;
                    $year->temp_active = $record['temp_active'];
                    
                    if ($year->update()) {
                        $ay = AcademicYear::where('active', true)->first();
                        $ay_id = $ay ? $ay->id : 0;
                        AuditLog::create([
                            'user_id' => $request->user()->id ?? 0,
                            'action' => 'UPDATE_ACADEMIC_YEAR_TEMP_ACTIVE_STATUS_BATCH',
                            'auditable_type' => AcademicYear::class,
                            'auditable_id' => $year->id,
                            'description' => 'Academic year ' . $year->year . ' temp active status changed in batch',
                            'old_values' => json_encode($old_year),
                            'new_values' => json_encode($year),
                            'ip_address' => $request->ip(),
                            'academic_year_id' => $ay_id
                        ]);
                    }
                }
            }
            
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }



}
