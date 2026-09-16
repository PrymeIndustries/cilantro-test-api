<?php

namespace App\Http\Controllers;

use App\Models\TemplateType;
use App\Models\AuditLog;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplateTypeController extends Controller
{
    public function index()
    {
        $template_types = TemplateType::all();
        return response()->json($template_types);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'type' => 'required|string'
            ]);

            $template_type = new TemplateType();
            $template_type->type = $request->input('type');
            if ($template_type->save()) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_TEMPLATE_TYPE',
                    'auditable_type' => TemplateType::class,
                    'auditable_id' => $template_type->id,
                    'description' => 'Template type ' . $template_type->type . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($template_type),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $template_type->refresh()
                ]); 
            }
        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'type' => 'required|string'
            ]);
            $template_type = TemplateType::where('id', $id)->first();
            $old_tt = $template_type->replicate();
            $template_type->type = $request->input('type');
            
            if ($template_type->update()) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_TEMPLATE_TYPE',
                    'auditable_type' => TemplateType::class,
                    'auditable_id' => $template_type->id,
                    'description' => 'Template type ' . $template_type->type . ' updated',
                    'old_values' => json_encode($old_tt),
                    'new_values' => json_encode($template_type),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $template_type->refresh()
                ]);
            }
        });
        

    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $template_type = TemplateType::where('id', $id)->first();
            if ($template_type->delete()) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'DELETE_TEMPLATE_TYPE',
                    'auditable_type' => TemplateType::class,
                    'auditable_id' => $id,
                    'description' => 'Template type ' . $template_type->type . ' deleted',
                    'old_values' => json_encode($template_type),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
            }
        });
        
    }




}
