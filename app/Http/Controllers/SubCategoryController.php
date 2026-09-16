<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

use App\Models\SubCategory;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubCategoryController extends Controller
{
    public function index()
    {
        $subclasses = SubCategory::with('category')->get();
        return response()->json($subclasses);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|max:10',
                'category_id' => 'required'
            ]);

            $subclass = new SubCategory();
            $subclass->name = $request->input('name');
            $subclass->code = $request->input('code');
            $subclass->category_id = $request->input('category_id');
            $subclass->class_master_id = $request->input('class_master_id');
            $subclass->save();

            $ay_id = $request->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => $request->user() ? $request->user()->id : 0,
                'action' => 'CREATE_SUBCATEGORY',
                'auditable_type' => SubCategory::class,
                'auditable_id' => $subclass->id,
                'description' => 'SubCategory ' . $subclass->name . ' created',
                'old_values' => null,
                'new_values' => json_encode($subclass),
                'ip_address' => $request->ip(),
                'academic_year_id' => $ay_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $subclass->refresh()
            ]);

        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|max:10',
                'category_id' => 'required'
            ]);
            $subclass = SubCategory::where('id', $id)->first();
            $old_subclass = $subclass->replicate();
            $subclass->name = $request->input('name');
            $subclass->code = $request->input('code');
            $subclass->category_id = $request->input('category_id');
            $subclass->class_master_id = $request->input('class_master_id');
            $subclass->update();

            $ay_id = $request->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => $request->user() ? $request->user()->id : 0,
                'action' => 'UPDATE_SUBCATEGORY',
                'auditable_type' => SubCategory::class,
                'auditable_id' => $subclass->id,
                'description' => 'SubCategory ' . $subclass->name . ' updated',
                'old_values' => json_encode($old_subclass),
                'new_values' => json_encode($subclass),
                'ip_address' => $request->ip(),
                'academic_year_id' => $ay_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $subclass->refresh()
            ]);

        });
        

    }

    public function destroy($id, \Illuminate\Http\Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $students = Student::where('sub_category_id', $id)->get();
            if (count($students) == 0) {
                $subclass = SubCategory::where('id', $id)->first();
                $subclass->delete();

                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 0,
                    'action' => 'DELETE_SUBCATEGORY',
                    'auditable_type' => SubCategory::class,
                    'auditable_id' => $id,
                    'description' => 'SubCategory ' . $subclass->name . ' deleted',
                    'old_values' => json_encode($subclass),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
                
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete this subject'
                ], 522);
            }

            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

}
