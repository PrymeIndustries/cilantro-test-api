<?php

namespace App\Http\Controllers;

use App\Models\SubjectPeriod;
use App\Models\AuditLog;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectPeriodController extends Controller
{
    public function index()
    {
        $subject_periods = SubjectPeriod::with('subject')->get();
        return response()->json($subject_periods);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'day' => 'required|string',
                'time' => 'required|string',
                'subject_id' => 'required'
            ]);
            $subject_period = new SubjectPeriod();
            $subject_period->day = $request->input('day');
            $subject_period->time = $request->input('time');
            $subject_period->subject_id = $request->input('subject_id');
            $subject_period->teacher_id = $request->input('teacher_id');
            $subject_period->subclass_id = $request->input('subclass_id');
            if ($subject_period->save()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_SUBJECT_PERIOD',
                    'auditable_type' => SubjectPeriod::class,
                    'auditable_id' => $subject_period->id,
                    'description' => 'Subject period created for subject ID ' . $subject_period->subject_id . ' on ' . $subject_period->day . ' at ' . $subject_period->time,
                    'old_values' => null,
                    'new_values' => json_encode($subject_period),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json($subject_period);
            }
        });
        
    }

    public function storeList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $errors = [];
                if (!isset($record['day'])) {
                    $errors[] = 'Day field is required';
                }
                if (!isset($record['time'])) {
                    $errors[] = 'Time field is required';
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
                $subject_period = new SubjectPeriod();
                $subject_period->day = $record['day'];
                $subject_period->time = $record['time'];
                $subject_period->subject_id = $record['subject_id'];
                $subject_period->teacher_id = $record['teacher_id'];
                $subject_period->subclass_id = $record['subclass_id'];
                if ($subject_period->save()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CREATE_SUBJECT_PERIOD_BATCH',
                        'auditable_type' => SubjectPeriod::class,
                        'auditable_id' => $subject_period->id,
                        'description' => 'Subject period created for subject ID ' . $subject_period->subject_id . ' in batch',
                        'old_values' => null,
                        'new_values' => json_encode($subject_period),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
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
                'day' => 'required|string',
                'time' => 'required|string',
                'subject_id' => 'required'
            ]);
            $subject_period = SubjectPeriod::where('id', $id)->first();
            if ($subject_period) {
                $old_sp = $subject_period->replicate();
                $subject_period->day = $request->input('day');
                $subject_period->time = $request->input('time');
                $subject_period->subject_id = $request->input('subject_id');
                $subject_period->teacher_id = $request->input('teacher_id');
                $subject_period->subclass_id = $request->input('subclass_id');
                if ($subject_period->update()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'UPDATE_SUBJECT_PERIOD',
                        'auditable_type' => SubjectPeriod::class,
                        'auditable_id' => $subject_period->id,
                        'description' => 'Subject period updated',
                        'old_values' => json_encode($old_sp),
                        'new_values' => json_encode($subject_period),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $subject_period->refresh()
                    ]);
                }
            }
        });
        

    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $subject_period = SubjectPeriod::where('id', $id)->first();
            if ($subject_period && $subject_period->delete()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => request()->user()->id ?? 0,
                    'action' => 'DELETE_SUBJECT_PERIOD',
                    'auditable_type' => SubjectPeriod::class,
                    'auditable_id' => $id,
                    'description' => 'Subject period deleted',
                    'old_values' => json_encode($subject_period),
                    'new_values' => null,
                    'ip_address' => request()->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
            }
        });
        
    }

    public function destroyList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $subject_period = SubjectPeriod::find($record['id']);
                if ($subject_period && $subject_period->delete()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'DELETE_SUBJECT_PERIOD_BATCH',
                        'auditable_type' => SubjectPeriod::class,
                        'auditable_id' => $record['id'],
                        'description' => 'Subject period deleted in batch',
                        'old_values' => json_encode($subject_period),
                        'new_values' => null,
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);
                }
            }
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }



}
