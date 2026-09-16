<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

use App\Models\AcademicYear;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\SubmissionType;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index()
    {
        $classes = Category::all();
        return response()->json($classes);
    }

    public function show($id)
    {
        $class = Category::find($id);
        return response()->json($class);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|max:10',
                //'fees' => 'required',
                'has_subcategories' => 'required',
                'is_examination_class' => 'required'
            ]);

            $class = new Category();
            $class->name = $request->input('name');
            $class->code = $request->input('code');
            $class->fees = $request->input('fees');
            $class->report_sheet_template_id = $request->input('report_sheet_template_id');
            $class->has_subcategories = $request->input('has_subcategories');
            $class->is_examination_class = $request->input('is_examination_class');
            if ($class->save()) {
                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 0,
                    'action' => 'CREATE_CATEGORY',
                    'auditable_type' => Category::class,
                    'auditable_id' => $class->id,
                    'description' => 'Category ' . $class->name . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($class),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                $class->subcategories()->saveMany(
                    array_map(function ($subclass) {
                        return new SubCategory([
                            'name' => $subclass['name'],
                            'code' => $subclass['code']
                        ]);
                    }, $request->subclasses ?? [])
                );

                return response()->json([
                    'status' => 'success',
                    'data' => $class->refresh()
                ]);
            }
        });
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'name' => 'required|string',
                'code' => 'required|max:10',
                //'fees' => 'required',
                'has_subcategories' => 'required',
                'is_examination_class' => 'required'
            ]);
            $class = Category::where('id', $id)->first();
            $old_class = $class->replicate();
            $class->name = $request->input('name');
            $class->code = $request->input('code');
            $class->fees = $request->input('fees');
            $class->report_sheet_template_id = $request->input('report_sheet_template_id');
            $class->has_subcategories = $request->input('has_subcategories');
            $class->is_examination_class = $request->input('is_examination_class');
            if ($class->update()) {
                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 0,
                    'action' => 'UPDATE_CATEGORY',
                    'auditable_type' => Category::class,
                    'auditable_id' => $class->id,
                    'description' => 'Category ' . $class->name . ' updated',
                    'old_values' => json_encode($old_class),
                    'new_values' => json_encode($class),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                if ($class->has_subcategories && $request->input('update_subcategories')) {
                    $subclasses = $request->input('subclasses');
                    foreach ($subclasses as $subclass) {
                        if (isset($subclass['id']) && $subclass['id'] != '') {
                            $record = SubCategory::find($subclass['id']);
                            $record->name = $subclass['name'];
                            $record->code = $subclass['code'];
                            $record->update();
                        } else {
                            $new_record =  new SubCategory();
                            $new_record->name = $subclass['name'];
                            $new_record->code = $subclass['code'];
                            $new_record->category_id = $id;
                            $new_record->save();
                        }
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'data' => $class->refresh()
                ]);
            }
        });
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $setting = Setting::where('slug', 'grading')->first();
            $students = Student::where('category_id', $id)->get();
            $subjects = Subject::where('category_id', $id)->get();
            $submissions = Submission::where('category_id', $id)->get();
            $grading_settings = [];
            foreach ($setting['parameters'] as $param) {
                if ($param['category_id'] == $id) {
                    $grading_settings[] = $param;
                }
            }
            if (count($students) == 0 && count($subjects) == 0 && count($submissions) == 0 && count($grading_settings) == 0) {
                $class = Category::where('id', $id)->first();
                $subclasses = SubCategory::where('category_id', $id)->get();
                if ($class->delete()) {
                    $ay_id = $request->input('academic_year_id');
                    if (!$ay_id) {
                        $ay = AcademicYear::where('active', true)->first();
                        $ay_id = $ay ? $ay->id : 0;
                    }
                    AuditLog::create([
                        'user_id' => $request->user() ? $request->user()->id : 0,
                        'action' => 'DELETE_CATEGORY',
                        'auditable_type' => Category::class,
                        'auditable_id' => $id,
                        'description' => 'Category ' . $class->name . ' deleted',
                        'old_values' => json_encode($class),
                        'new_values' => null,
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $ay_id
                    ]);
                    foreach ($subclasses as $subclass){
                        $subclass->delete();
                    }
                    return response()->json([
                        'status' => 'success'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete this class'
                ], 522);
            }
        }); 
    }



    public function getStudents($id)
    {
        $active_ay = AcademicYear::where('active', true)->first();
        $data = Student::with(['category', 'sub_category', 'guardian'])
                        ->where('academic_year_id', $active_ay->id)
                        ->where('category_id', $id)
                        ->get();
        $sorted_data = collect($data)->sortBy('full_name');
        $students = [];
        foreach ($sorted_data as $student) {
            if ($student['date_of_birth'] == null) {
                $student['date_of_birth'] = 'N/A';
            }
            if ($student['guardian_id'] == null) {
                $student['guardian_id'] = 0;
            }
            if ($student['present'] == true || $student['present'] == 1) {
                $students[] = $student;
            }
        }
        return response()->json($students);
    }

    public function getStudentSubjects($id)
    {
        $active_ay = AcademicYear::where('active', true)->first();
        $data = StudentSubject::with('subject')
                    ->where('academic_year_id', $active_ay->id)
                    ->get();
        $student_subjects = [];
        foreach ($data as $student_subject) {
            if ($student_subject['subject']['category_id'] == $id) {
                $student_subjects[] = $student_subject;
            }
        }
        return response()->json($student_subjects);
    }

    public function getSubmissionTypes($id)
    {
        $submission_types = SubmissionType::where('category_id', $id)
                    ->where('active', true)
                    ->get();
        return response()->json($submission_types);
    }




}
