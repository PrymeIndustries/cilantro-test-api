<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AcademicYear;
use App\Models\Category;
use App\Models\Student;
use App\Models\Template ;
use App\Models\TemplatePlaceholder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TemplatePlaceholderController extends Controller
{
    public function index()
    {
        return response()->json(TemplatePlaceholder::orderBy('system', 'asc')->get());
    }

    public function getNonSystemPlaceholders(Template $template): JsonResponse
    {
        $regex = "/\{.*?\}/";
        preg_match_all($regex, $template->template, $matches);
        $plcs = [];
        if (count($matches)) {
            $plcs = TemplatePlaceholder::nonSystem()->whereIn('value', $matches[0])->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $plcs,
            'matches' => $matches
        ]);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $request->merge([
                'value' => ('{' . Str::slug($request->input('label')) . '}')
            ]);
            $this->validate($request, [
                'label' => 'required',
                'value' => [Rule::unique('template_placeholders', 'value')],
                'type' => ['required'],
                'description' => 'nullable',
            ], [
                'value.unique' => 'A placeholder with the same label already exists.'
            ]);

            $plc = new TemplatePlaceholder();
            $plc->text = $request->input('label');
            $plc->value = $request->input('value');
            $plc->model_key = $request->input('type');
            $plc->description = $request->input('description');
            if ($plc->save()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_TEMPLATE_PLACEHOLDER',
                    'auditable_type' => TemplatePlaceholder::class,
                    'auditable_id' => $plc->id,
                    'description' => 'Placeholder ' . $plc->text . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($plc),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $plc
                ]);
            }
        });
        
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $plc = TemplatePlaceholder::where('id', $id)->first();
            if ($plc->system) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'System template placeholders cannot be deleted.'
                ]);
            }
            if ($plc->delete()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'DELETE_TEMPLATE_PLACEHOLDER',
                    'auditable_type' => TemplatePlaceholder::class,
                    'auditable_id' => $id,
                    'description' => 'Placeholder ' . $plc->text . ' deleted',
                    'old_values' => json_encode($plc),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data deleted.'
                ]);
            }
        });
        
    }




}
