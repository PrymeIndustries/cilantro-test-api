<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Student;
use App\Models\StudentCode;
use App\Models\DisciplineRecord;
use App\Models\StudentSubject;
use App\Models\SubjectPeriod;
use App\Models\StudentMark;
use App\Models\Guardian;
use App\Models\Result;
use App\Models\ReportSheet;
use App\Packages\JsonStorage\JsonStorage;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['category', 'sub_category', 'guardian'])->get();
        return response()->json($students);
    }

    public function getCurrentAYStudents()
    {
        $active_ay = AcademicYear::where('active', true)->first();
        $data = Student::with(['category', 'sub_category', 'guardian'])
                        ->where('academic_year_id', $active_ay->id)
                        ->get();
        $students = [];
        foreach ($data as $student) {
            if ($student['date_of_birth'] == null) {
                $student['date_of_birth'] = 'N/A';
            }
            if ($student['guardian_id'] == null) {
                $student['guardian_id'] = 0;
            }
            $students[] = $student;
        }
        return response()->json($students);
    }

    public function getStudents($ay_id)
    {
        $students = Student::with(['category', 'sub_category', 'guardian'])
                        ->where('academic_year_id', $ay_id)
                        ->get();
        return response()->json($students);
    }

    public function show($id)
    {
        $student = Student::find($id);
        return response()->json($student);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'full_name' => 'required',
                //'date_of_birth' => 'required',
                'gender' => 'required',
                'category_id' => 'required'
            ]);
            $student = new Student();
            $student->full_name = $request->input('full_name');
            $student->date_of_birth = $request->input('date_of_birth');
            $student->matricule = $request->input('matricule');
            $student->email = $request->input('email');
            $student->telephone = $request->input('telephone');
            $student->gender = $request->input('gender');
            $student->address = $request->input('address');
            $student->category_id = $request->input('category_id');
            $student->sub_category_id = $request->input('sub_category_id');
            $student->guardian_id = $request->input('guardian_id');

            if ($student->guardian_id != null) {
                $guardian = Guardian::find($student->guardian_id);
                $student->guardian_name = $guardian->name;
            } else {
                $student->guardian_name = $request->input('guardian_name');
            }
            
            $student->fees = $request->input('fees');
            if ($request->input('fees_amount_paid') != null && $request->input('fees_amount_paid') != '') {
                $student->fees_amount_paid = $request->input('fees_amount_paid');
                $student->fees_history = $request->input('fees_history');
                if ($request->fees_status == 'complete') {
                    $student->paid_fees = 1;
                }
                if ($request->fees_status == 'incomplete' || $request->fees_status == 'unpaid') {
                    $student->paid_fees = 0;
                }
                $student->fees_status = $request->fees_status;
            }

            if ($request->photo) {
                $student->photo = $request->photo;
                $student->photo_thumbnail = $request->photo;
            }

            $ay_id = $request->input('academic_year_id');
            if ($ay_id) {
                $student->academic_year_id = $ay_id;
            } else {
                $active_ay = AcademicYear::where('active', true)->first();
                $student->academic_year_id = $active_ay->id;
            }

            if ($student->save()) {
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_STUDENT',
                    'auditable_type' => Student::class,
                    'auditable_id' => $student->id,
                    'description' => 'Student ' . $student->full_name . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($student),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $student->academic_year_id
                ]);

                $student->subjects()->saveMany(
                    array_map(function ($subject) use ($student) {
                        return new StudentSubject([
                            'subject_id' => $subject['id'],
                            'academic_year_id' => $student->academic_year_id
                        ]);
                    }, $request->subjects)
                );

                $student_code = new StudentCode();
                $student_code->code = $this->_generateCode(7, $student->id);
                $student_code->student_id = $student->id;
                $student_code->save();

                return response()->json($student);
            }
        });
        
    }

    public function storeList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $errors = [];
                if (!isset($record['full_name'])) {
                    $errors[] = 'Full name field is required';
                }
                // if (!isset($record['date_of_birth'])) {
                //     $errors[] = 'Date of birth field is required';
                // }
                if (!isset($record['gender'])) {
                    $errors[] = 'Gender field is required';
                }
                if (!isset($record['category_id'])) {
                    $errors[] = 'Category id field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }
                $student = new Student();
                $student->full_name = $record['full_name'];
                $student->date_of_birth = $record['date_of_birth'];
                $student->matricule = $record['matricule'];
                $student->email = $record['email'];
                $student->telephone = $record['telephone'];
                $student->gender = $record['gender'];
                $student->address = $record['address'];
                $student->category_id = $record['category_id'];
                $student->sub_category_id = $record['sub_category_id'];
                $student->guardian_id = $record['guardian_id'];

                if ($student->guardian_id != null) {
                    $guardian = Guardian::find($student->guardian_id);
                    $student->guardian_name = $guardian->name;
                } else {
                    $student->guardian_name = $record['guardian_name'];
                }

                $student->fees = $record['fees'];

                if (isset($record['academic_year_id'])) {
                    $ay_id = $record['academic_year_id'];
                } else {
                    $ay_id = null;
                }

                if ($ay_id != null) {
                    $student->academic_year_id = $ay_id;
                } else {
                    $active_ay = AcademicYear::where('active', true)->first();
                    $student->academic_year_id = $active_ay->id;
                }
                if ($student->save()) {
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CREATE_STUDENT_BATCH',
                        'auditable_type' => Student::class,
                        'auditable_id' => $student->id,
                        'description' => 'Student ' . $student->full_name . ' created in batch',
                        'old_values' => null,
                        'new_values' => json_encode($student),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $student->academic_year_id
                    ]);

                    $student->subjects()->saveMany(
                        array_map(function ($subject) use ($student) {
                            return new StudentSubject([
                                'subject_id' => $subject['id'],
                                'academic_year_id' => $student->academic_year_id
                            ]);
                        }, $record['subjects'])
                    );

                    $student_code = new StudentCode();
                    $student_code->code = $this->_generateCode(7, $student->id);
                    $student_code->student_id = $student->id;
                    $student_code->save();
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
                'full_name' => 'required',
                //'date_of_birth' => 'required',
                'gender' => 'required',
                'category_id' => 'required'
            ]);
            $student = Student::find($id);
            $old_student = $student->replicate();
            $student->full_name = $request->input('full_name');
            $student->date_of_birth = $request->input('date_of_birth');
            $student->matricule = $request->input('matricule');
            $student->email = $request->input('email');
            $student->telephone = $request->input('telephone');
            $student->gender = $request->input('gender');
            $student->address = $request->input('address');
            $student->category_id = $request->input('category_id');
            $student->sub_category_id = $request->input('sub_category_id');
            $student->guardian_id = $request->input('guardian_id');

            if ($student->guardian_id != null) {
                $guardian = Guardian::find($student->guardian_id);
                $student->guardian_name = $guardian->name;
            } else {
                $student->guardian_name = $request->input('guardian_name');
            }

            $student->fees = $request->input('fees');
            $student->fees_amount_paid = $request->input('fees_amount_paid');
            $student->fees_history = $request->input('fees_history');

            if ($request->input('update_subjects')) {
                $new_subjects = $request->input('subject_ids');
                $old_subjects = StudentSubject::where('student_id', $student->id)->get();
                foreach ($old_subjects as $subject) {
                    $subject->delete();
                }
                foreach ($new_subjects as $subject_id) {
                    $student_subject = new StudentSubject();
                    $student_subject->student_id = $student->id;
                    $student_subject->subject_id = $subject_id;
                    $student_subject->academic_year_id = $student->academic_year_id;
                    $student_subject->save();
                }
            }

            if ($student->update()) {
                if ($request->photo) {
                    $student->photo = $request->photo;
                    $student->photo_thumbnail = $request->photo;
                    $student->update();
                }

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_STUDENT',
                    'auditable_type' => Student::class,
                    'auditable_id' => $student->id,
                    'description' => 'Student ' . $student->full_name . ' updated',
                    'old_values' => json_encode($old_student),
                    'new_values' => json_encode($student),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $student->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $student->refresh()
                ]);
            }
        });
        

    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $marks = StudentMark::where('student_id', $id)->get();
            $results = Result::where('student_id', $id)->get();
            $report_sheets = ReportSheet::where('student_id', $id)->get();
            if (count($marks) == 0 && count($results) == 0 && count($report_sheets) == 0) {
                $student = Student::find($id);
                $student_code = StudentCode::where('student_id', $id)->first();
                $subjects = StudentSubject::where('student_id', $id)->get();
                if ($student->delete()) {
                    AuditLog::create([
                        'user_id' => request()->user()->id ?? 0,
                        'action' => 'DELETE_STUDENT',
                        'auditable_type' => Student::class,
                        'auditable_id' => $id,
                        'description' => 'Student ' . $student->full_name . ' deleted',
                        'old_values' => json_encode($student),
                        'new_values' => null,
                        'ip_address' => request()->ip(),
                        'academic_year_id' => $student->academic_year_id
                    ]);

                    $student_code->delete();
                    foreach ($subjects as $subject) {
                        $subject->delete();
                    }
                    return response()->json([
                        'status' => 'success'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete this student'
                ], 522);
            }
        });
        
    }

    public function changeStatus($id)
    {
        return DB::transaction(function () use ($id) {
            $student = Student::find($id);
            if ($student) {
                $student->present = !$student->present;
                if ($student->save()) {
                    AuditLog::create([
                        'user_id' => request()->user()->id ?? 0,
                        'action' => 'CHANGE_STUDENT_STATUS',
                        'auditable_type' => Student::class,
                        'auditable_id' => $student->id,
                        'description' => 'Student ' . $student->full_name . ' status changed to ' . ($student->present ? 'Present' : 'Absent'),
                        'old_values' => json_encode(['present' => !$student->present]),
                        'new_values' => json_encode(['present' => $student->present]),
                        'ip_address' => request()->ip(),
                        'academic_year_id' => $student->academic_year_id
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $student->refresh()
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student record not found!'
                ], 522);
            }
        });
        
    }

    public function changeFeesStatus($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $student = Student::find($id);
            if ($student) {
                $old_student_fees = [
                    'fees_amount_paid' => $student->fees_amount_paid,
                    'fees_status' => $student->fees_status
                ];
                $student->fees_amount_paid = $request->input('fees_amount_paid');
                $student->fees_history = $request->input('fees_history');
                if ($request->status == 'complete') {
                    $student->paid_fees = 1;
                }
                if ($request->status == 'incomplete' || $request->status == 'unpaid') {
                    $student->paid_fees = 0;
                }
                $student->fees_status = $request->status;
                if ($student->save()) {
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CHANGE_STUDENT_FEES_STATUS',
                        'auditable_type' => Student::class,
                        'auditable_id' => $student->id,
                        'description' => 'Student ' . $student->full_name . ' fees status changed',
                        'old_values' => json_encode($old_student_fees),
                        'new_values' => json_encode([
                            'fees_amount_paid' => $student->fees_amount_paid,
                            'fees_status' => $student->fees_status
                        ]),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $student->academic_year_id
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $student->refresh()
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student record not found!'
                ], 522);
            }
        });
        
    }

    public function isRepeater($id)
    {
        $students = Student::all();
        $current_student = Student::where('id', $id)->first();
        $ays = AcademicYear::all();
        $active_ay = AcademicYear::where('active', true)->first();
        $prev_ay = null;
        if (count((array)$active_ay) != 0) {
            foreach ($ays as $index => $obj) {
                if ($obj['id'] == $active_ay->id && $obj['year'] == $active_ay->year && $obj['status'] == $active_ay->status) {
                    if ($index != 0) {
                        $prev_ay = $ays[$index - 1];
                    }
                    break;
                }
            }
        }
        if ($prev_ay != null) {
            $std = [];
            foreach ($students as $student) {
                if ($student->full_name == $current_student->full_name &&
                    $student->date_of_birth == $current_student->date_of_birth &&
                    $student->guardian_id == $current_student->guardian_id &&
                    $student->academic_year_id == $prev_ay->id
                ) {
                    $std[] = $student;
                }
            }
            if (count($std) > 0) {
                $student = $std[0];
                if ($student->category_id == $current_student->category_id) {
                    return 'true';
                } else {
                    return 'false';
                }
            } else {
                return 'false';
            }
        } else {
            return 'n/a';
        }
    }

    public function isRepeaterList(Request $request)
    {
        $records = $request->records;
        $data = [];
        foreach ($records as $record) {
            $repeater = $this->isRepeater($record['id']);
            $res = [
                'student_id' => $record['id'],
                'repeater' => $repeater
            ];
            $data[] = $res;
        }
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function getSubjects($id)
    {
        $student_subjects = StudentSubject::with('subject')
                                ->where('student_id', $id)
                                ->get();
        $subject_periods = SubjectPeriod::with('subject')->get();
        $subjects = [];
        foreach ($student_subjects as $ss) {
            $periods = [];
            foreach ($subject_periods as $sp) {
                if ($sp->subject_id == $ss->subject_id) {
                    $periods[] = $sp;
                }
            }
            $ss['subject']['periods'] = $periods;
            $subjects[] = $ss;
        }
        return response()->json($subjects);
    }


    public function getDisciplineRecords()
    {
        $discipline_records = DisciplineRecord::with('student')->get();
        return response()->json($discipline_records);
    }

    public function getRecords($ay_id)
    {
        $discipline_records = DisciplineRecord::with('student')
                                ->where('academic_year_id', $ay_id)
                                ->get();
        return response()->json($discipline_records);
    }

    public function storeDisciplineRecord(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'note' => 'required',
                'hours_of_punishment' => 'required',
                'number_of_warnings' => 'required',
                'days_of_suspension' => 'required',
                'hours_of_absences' => 'required',
                'student_id' => 'required',
            ]);
            $discipline_record = new DisciplineRecord();
            $discipline_record->note = $request->input('note');
            $discipline_record->hours_of_punishment = $request->input('hours_of_punishment');
            $discipline_record->number_of_warnings = $request->input('number_of_warnings');
            $discipline_record->days_of_suspension = $request->input('days_of_suspension');
            $discipline_record->hours_of_absences = $request->input('hours_of_absences');
            $discipline_record->student_id = $request->input('student_id');

            $active_term = Term::where('active', true)->first();
            if (count((array)$active_term) == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active term'
                ], 522);
            }
            $discipline_record->term_id = $active_term->id;

            $active_ay = AcademicYear::where('active', true)->first();
            $discipline_record->academic_year_id = $active_ay->id;

            if ($discipline_record->save()) {
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_DISCIPLINE_RECORD',
                    'auditable_type' => DisciplineRecord::class,
                    'auditable_id' => $discipline_record->id,
                    'description' => 'Discipline record created for student ID ' . $discipline_record->student_id,
                    'old_values' => null,
                    'new_values' => json_encode($discipline_record),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $discipline_record->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $discipline_record->refresh()
                ]);
            }
        });
        
    }

    public function updateDisciplineRecord($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'note' => 'required',
                'hours_of_punishment' => 'required',
                'number_of_warnings' => 'required',
                'days_of_suspension' => 'required',
                'hours_of_absences' => 'required',
                'student_id' => 'required'
            ]);
            $discipline_record = DisciplineRecord::where('id', $id)->first();
            $old_record = $discipline_record->replicate();
            $discipline_record->note = $request->input('note');
            $discipline_record->hours_of_punishment = $request->input('hours_of_punishment');
            $discipline_record->number_of_warnings = $request->input('number_of_warnings');
            $discipline_record->days_of_suspension = $request->input('days_of_suspension');
            $discipline_record->hours_of_absences = $request->input('hours_of_absences');
            $discipline_record->student_id = $request->input('student_id');
            if ($discipline_record->update()) {
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_DISCIPLINE_RECORD',
                    'auditable_type' => DisciplineRecord::class,
                    'auditable_id' => $id,
                    'description' => 'Discipline record updated for student ID ' . $discipline_record->student_id,
                    'old_values' => json_encode($old_record),
                    'new_values' => json_encode($discipline_record),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $discipline_record->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $discipline_record->refresh()
                ]);
            }
        });
        

    }

    public function destroyDisciplineRecord($id)
    {
        return DB::transaction(function () use ($id) {
            $discipline_record = DisciplineRecord::where('id', $id)->first();
            if ($discipline_record->delete()) {
                AuditLog::create([
                    'user_id' => request()->user()->id ?? 0,
                    'action' => 'DELETE_DISCIPLINE_RECORD',
                    'auditable_type' => DisciplineRecord::class,
                    'auditable_id' => $id,
                    'description' => 'Discipline record deleted for student ID ' . $discipline_record->student_id,
                    'old_values' => json_encode($discipline_record),
                    'new_values' => null,
                    'ip_address' => request()->ip(),
                    'academic_year_id' => $discipline_record->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
            }
        });
        
    }



    public function _generateCode($n, $id)
    {
        $store = new JsonStorage();
        $sc = $store->get('school', false);
        $digits = '123456789';
        $randomDigits = '';
        for ($i=0; $i<$n; $i++) {
            $randomDigits .= $digits[rand(0, 8)];
        }
        $rand_id = ((int)$randomDigits) / 10000;
        $code = $sc['code'][0] . $randomDigits . 'C1-S' . round($rand_id)-10;
        return $code;
    }




}
