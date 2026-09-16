<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

use App\Models\Setting;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all();
        return response()->json($settings);
    }
    
    public function store(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required',
                'parameters' => 'required'
            ]);

            $setting = new Setting();
            $setting->name = $request->input('name');
            $setting->parameters = $request->input('parameters');
            $setting->slug = $request->input('slug');
            $setting->save();

            $ay_id = $request->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => $request->user() ? $request->user()->id : 0,
                'action' => 'CREATE_SETTING',
                'auditable_type' => Setting::class,
                'auditable_id' => $setting->id,
                'description' => 'Setting ' . $setting->name . ' created',
                'old_values' => null,
                'new_values' => json_encode($setting),
                'ip_address' => $request->ip(),
                'academic_year_id' => $ay_id
            ]);
            return response()->json([
                'status' => 'success',
                'data' => $setting->refresh()
            ]);
        });
        
    }

    public function update($id, Request $request): JsonResponse
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'name' => 'required',
                'parameters' => 'required'
            ]);

            $setting = Setting::where('id', $id)->first();
            $old_setting = $setting->replicate();
            $setting->name = $request->input('name');
            $setting->parameters = $request->input('parameters');
            $setting->slug = $request->input('slug');
            $setting->update();

            $ay_id = $request->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => $request->user() ? $request->user()->id : 0,
                'action' => 'UPDATE_SETTING',
                'auditable_type' => Setting::class,
                'auditable_id' => $setting->id,
                'description' => 'Setting ' . $setting->name . ' updated',
                'old_values' => json_encode($old_setting),
                'new_values' => json_encode($setting),
                'ip_address' => $request->ip(),
                'academic_year_id' => $ay_id
            ]);
            return response()->json([
                'status' => 'success',
                'data' => $setting->refresh() //$setting->load('user')->refresh()
            ]);
        });
        

    }

    public function destroy($id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $setting = Setting::find($id);
            $setting->delete();

            $ay_id = request()->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => request()->user() ? request()->user()->id : 0,
                'action' => 'DELETE_SETTING',
                'auditable_type' => Setting::class,
                'auditable_id' => $id,
                'description' => 'Setting ' . $setting->name . ' deleted',
                'old_values' => json_encode($setting),
                'new_values' => null,
                'ip_address' => request()->ip(),
                'academic_year_id' => $ay_id
            ]);
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }


}
