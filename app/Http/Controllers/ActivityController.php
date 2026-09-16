<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::all();
        return response()->json($activities);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|string',
                'periods' => 'required|array|min:1',
                'periods.*' => 'required'
            ]);
            $activity = new Activity();
            $activity->name = $request->input('name');
            $activity->code = $request->input('code');
            $activity->periods = $request->input('periods');
            if ($activity->save()) {
                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_ACTIVITY',
                    'auditable_type' => Activity::class,
                    'auditable_id' => $activity->id,
                    'description' => 'Activity ' . $activity->name . ' (' . $activity->code . ') created',
                    'old_values' => null,
                    'new_values' => json_encode($activity),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json($activity);
            }
        });    
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|string',
                'periods' => 'required|array|min:1',
                'periods.*' => 'required'
            ]);
            $activity = Activity::where('id', $id)->first();
            $old_activity = $activity->replicate();
            $activity->name = $request->input('name');
            $activity->code = $request->input('code');
            $activity->periods = $request->input('periods');
            if ($activity->update()) {
                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_ACTIVITY',
                    'auditable_type' => Activity::class,
                    'auditable_id' => $activity->id,
                    'description' => 'Activity ' . $activity->name . ' updated',
                    'old_values' => json_encode($old_activity),
                    'new_values' => json_encode($activity),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $activity->refresh()
                ]);
            }
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $activity = Activity::where('id', $id)->first();
            if ($activity->delete()) {
                $ay_id = request()->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => request()->user()->id ?? 0,
                    'action' => 'DELETE_ACTIVITY',
                    'auditable_type' => Activity::class,
                    'auditable_id' => $id,
                    'description' => 'Activity ' . $activity->name . ' deleted',
                    'old_values' => json_encode($activity),
                    'new_values' => null,
                    'ip_address' => request()->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
            }
        });   
    }



}
