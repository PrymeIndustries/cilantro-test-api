<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AcademicYear;
use App\Models\Template;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::all();
        return response()->json($templates);
    }


    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'title' => 'required|string',
                'value' => 'required',
                'template_type_id' => 'required'
            ]);
            $template = new Template();
            $template->title = $request->input('title');
            $template->value = $request->input('value');
            $template->template_type_id = $request->input('template_type_id');
            if ($template->save()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_TEMPLATE',
                    'auditable_type' => Template::class,
                    'auditable_id' => $template->id,
                    'description' => 'Template ' . $template->title . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($template),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $template->refresh()
                ]);
            }
        });
        
    }


    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'title' => 'required|string',
                'value' => 'required',
                'template_type_id' => 'required'
            ]);
            $template = Template::where('id', $id)->first();
            $old_template = $template->replicate();
            $template->title = $request->input('title');
            $template->value = $request->input('value');
            $template->template_type_id = $request->input('template_type_id');
            if ($template->update()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_TEMPLATE',
                    'auditable_type' => Template::class,
                    'auditable_id' => $template->id,
                    'description' => 'Template ' . $template->title . ' updated',
                    'old_values' => json_encode($old_template),
                    'new_values' => json_encode($template),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $template->refresh()
                ]);
            }
        });
        

    }


    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $template = Template::where('id', $id)->first();
            $classes = Category::where('report_sheet_template_id', $id)->get();
            if ($template->system) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'System templates cannot be deleted.'
                ]);
            }
            if (count($classes) == 0) {
                if ($template->delete()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'DELETE_TEMPLATE',
                        'auditable_type' => Template::class,
                        'auditable_id' => $id,
                        'description' => 'Template ' . $template->title . ' deleted',
                        'old_values' => json_encode($template),
                        'new_values' => null,
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Data has been deleted successfully!'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete this template'
                ], 522);
            }
        });
        
    }




}
