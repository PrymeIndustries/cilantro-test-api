<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentCode;
use App\Models\StudentSubject;
use App\Models\StudentMark;
use App\Models\Result;
use App\Models\ReportSheet;
use App\Models\Message;
use App\Models\AuditLog;
use App\Models\GuardianCode;
use App\Packages\JsonStorage\JsonStorage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuardianController extends Controller
{

    public function index()
    {
        $guardians = Guardian::all();
        return response()->json($guardians);
    }

    public function getGuardians($ay_id)
    {
        $guardians = Guardian::all();
        $students = Student::where('academic_year_id', $ay_id)->get();
        $filtered_guardians = [];
        foreach ($guardians as $guardian) {
            $children = array_filter($students->toArray(), function ($student) use ($guardian) {
                return $student['guardian_id'] == $guardian->id;
            });
            if (count($children) > 0) {
                $filtered_guardians[] = $guardian;
            }
        }
        return response()->json($filtered_guardians);
    }

    public function show($id)
    {
        $guardian = Guardian::find($id);
        return response()->json($guardian);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required',
                'telephone' => 'required|min:6',
                'gender' => 'required'
            ]);
            $guardian = new Guardian();
            $guardian->name = $request->input('name');
            $guardian->email = $request->input('email');
            $guardian->telephone = $request->input('telephone');
            $guardian->telephone_2 = $request->input('telephone_2');
            $guardian->gender = $request->input('gender');
            $guardian->address = $request->input('address');
            $guardian->occupation = $request->input('occupation');

            if ($request->photo) {
                $guardian->photo = $request->photo;
                $guardian->photo_thumbnail = $request->photo;
            }

            $ay_id = $request->input('academic_year_id');
            if ($ay_id) {
                $guardian->academic_year_id = $ay_id;
            } else {
                $active_ay = AcademicYear::where('active', true)->first();
                $guardian->academic_year_id = $active_ay->id;
            }

            if ($guardian->save()) {
                if ($request->input('children')) {
                    $this->_storeChildren($guardian, $request->input('children'));
                }
                if ($request->input('children_update')) {
                    $this->_attachChildren($guardian, $request->input('children_update'));
                }

                $guardian_code = new GuardianCode();
                $guardian_code->code = $this->_generateCode(10, $guardian->id);
                $guardian_code->guardian_id = $guardian->id;
                $guardian_code->save();

                if ($guardian->email != null && $guardian->email != '') {
                    $this->_emailLoginDetails($guardian->name, $guardian->email, $guardian_code->code, $request->input('schoolCID'));
                }

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_GUARDIAN',
                    'auditable_type' => Guardian::class,
                    'auditable_id' => $guardian->id,
                    'description' => 'Guardian ' . $guardian->name . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($guardian),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $guardian->academic_year_id
                ]);

                return response()->json($guardian);
            }
        });
       
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required',
                'telephone' => 'required|min:6',
                'gender' => 'required'
            ]);
            $guardian = Guardian::where('id', $id)->first();
            $old_guardian = $guardian->replicate();
            $guardian->name = $request->input('name');
            $guardian->email = $request->input('email');
            $guardian->telephone = $request->input('telephone');
            $guardian->telephone_2 = $request->input('telephone_2');
            $guardian->gender = $request->input('gender');
            $guardian->address = $request->input('address');
            $guardian->occupation = $request->input('occupation');

            if ($request->input('remove_children')) {
                $students = $request->input('remove_children');
                foreach ($students as $student_id) {
                    $student = Student::find($student_id);
                    $student->guardian_id = null;
                    $student->guardian_name = null;
                    $student->save();
                }
            }

            if ($guardian->update()) {
                if ($request->photo) {
                    $guardian->photo = $request->photo;
                    $guardian->photo_thumbnail = $request->photo;
                    $guardian->update();
                }

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'UPDATE_GUARDIAN',
                    'auditable_type' => Guardian::class,
                    'auditable_id' => $guardian->id,
                    'description' => 'Guardian ' . $guardian->name . ' updated',
                    'old_values' => json_encode($old_guardian),
                    'new_values' => json_encode($guardian),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $guardian->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $guardian->refresh()
                ]);
            }
        });
        
    }

    public function updateList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $errors = [];
                if (!isset($record['name'])) {
                    $errors[] = 'Name field is required';
                }
                if (!isset($record['email'])) {
                    $errors[] = 'Email field is required';
                }
                if (!isset($record['telephone'])) {
                    $errors[] = 'Telephone field is required';
                }
                if (!isset($record['gender'])) {
                    $errors[] = 'Gender field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }
                $guardian = Guardian::where('id', $record['id'])->first();
                if ($guardian) {
                    $old_guardian = $guardian->replicate();
                    $guardian->academic_year_id = $record['academic_year_id'];
                    if ($guardian->save()) {
                        AuditLog::create([
                            'user_id' => $request->user()->id ?? 0,
                            'action' => 'UPDATE_GUARDIAN_BATCH',
                            'auditable_type' => Guardian::class,
                            'auditable_id' => $guardian->id,
                            'description' => 'Guardian ' . $guardian->name . ' updated in batch',
                            'old_values' => json_encode($old_guardian),
                            'new_values' => json_encode($guardian),
                            'ip_address' => $request->ip(),
                            'academic_year_id' => $guardian->academic_year_id
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $children = Student::where('guardian_id', $id)->get();
            $active_children = [];
            if (count($children) > 0) {
                foreach ($children as $child) {
                    $marks = StudentMark::where('student_id', $child->id)->get();
                    $results = Result::where('student_id', $child->id)->get();
                    $report_sheets = ReportSheet::where('student_id', $child->id)->get();
                    if (count($marks) > 0 || count($results) > 0 || count($report_sheets) > 0) {
                        $active_children[] = $child;
                    }
                }  
            }  
            if (count($children) == 0 || count($active_children) == 0) {
                $guardian = Guardian::find($id);
                $guardian_code = GuardianCode::where('guardian_id', $id)->first();
                if ($guardian->delete()) {
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'DELETE_GUARDIAN',
                        'auditable_type' => Guardian::class,
                        'auditable_id' => $id,
                        'description' => 'Guardian ' . $guardian->name . ' deleted',
                        'old_values' => json_encode($guardian),
                        'new_values' => null,
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $guardian->academic_year_id
                    ]);

                    $guardian_code->delete();
                    if (count($children) > 0 && count($active_children) == 0) {
                        foreach ($children as $child) {
                            $child->guardian_id = null;
                            $child->save();
                        }
                    }
                    return response()->json([
                        'status' => 'success'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete this guardian'
                ], 522);
            }
        });
        
    }

    public function changeStatus($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $guardian = Guardian::find($id);
            if ($guardian) {
                $guardian->active = !$guardian->active;
                if ($guardian->active) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    $guardian->academic_year_id = $active_ay->id;
                }
                if ($guardian->save()) {
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CHANGE_GUARDIAN_STATUS',
                        'auditable_type' => Guardian::class,
                        'auditable_id' => $guardian->id,
                        'description' => 'Guardian ' . $guardian->name . ' status changed to ' . ($guardian->active ? 'Active' : 'Inactive'),
                        'old_values' => json_encode(['active' => !$guardian->active]),
                        'new_values' => json_encode(['active' => $guardian->active]),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $guardian->academic_year_id
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $guardian->refresh()
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Guardian record not found!'
                ]);
            }
        });   
    }

    public function getGuardianStudents($id, Request $request)
    {
        $children = Student::with(['category', 'sub_category', 'guardian'])
                        ->where('academic_year_id', $request->academic_year_id)
                        ->where('guardian_id', $id)
                        ->get();
        return response()->json($children);
    }

    public function getChildren($id, Request $request)
    {
        $data = Student::with(['category', 'sub_category', 'guardian'])
                        ->where('academic_year_id', $request->academic_year_id)
                        ->where('guardian_id', $id)
                        ->get();
        $children = [];
        foreach ($data as $child) {
            if ($child['date_of_birth'] == null) {
                $child['date_of_birth'] = 'N/A';
            }
            $children[] = $child;
        }
        return response()->json($children);
    }

    public function getMessages($code)
    {
        $data = Message::latest()->cursor();
        $messages = [];
        foreach ($data as $msg) {
            if (isset($msg->to[0]) && ($msg->to[0] == $code || $msg->to[0] == 'G0X')) {
                $messages[] = $msg;
            }
        }
        return response()->json($messages);
    }


    
    public function search(Request $request)
    {
        $query = $request->name;
        $results = Guardian::where('name', 'like', '%' . $query . '%')->get();
        return response()->json([
            'status' => 'success',
            'data' => $results
        ], 200);
    }


    public function mobileLogin(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
            'server_api' => 'required'
        ]);
        $code = $request->input('code');
        $server_api = $request->input('server_api');
        $store = new JsonStorage();
        $school = $store->get('school', false);
        $license = $store->get('license', false);
        $today = Carbon::now();

        $guardian_code = GuardianCode::with('guardian')
                ->cursor()
                ->filter(fn ($item) => $item->code == $code)
                ->first();
                
        if (!$guardian_code || ($guardian_code->code != $code)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid code entered, no guardian found. Please correct your code.'
            ], 522);
        }
        
        $api = env('APP_URL');
        if ($server_api != $api) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid server api entered, no server found. Please correct the server api.'
            ], 522);
        }

        if (!$guardian_code->guardian['active']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, you cannot sign in because your account is inactive. Please contact your school.'
            ], 522);
        }

        if ($license) {
            if (isset($license['expires'])) {
                try {
                    $decrypted = Crypt::decryptString($license['expires']);
                    $expiration = Carbon::parse($decrypted);
                    $active = $expiration->isAfter($today);
                    if (!$active) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Expired school license. Please contact your school.'
                        ], 502);
                    }
                } catch (DecryptException | InvalidFormatException $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Invalid school license. Please contact your school."
                    ], 502);
                }
            }
        }

        $guardian_code['guardian']['school_code'] = $school['code'];
        $guardian_code['guardian']['mobile'] = $school['mobile'];
        $guardian_code['guardian']['app_version'] = env('APP_VERSION');

        return response()->json([
            'status' => 'success',
            'data' => $guardian_code['guardian']
        ]);
    }


    
    public function _generateCode($n, $id)
    {
        $store = new JsonStorage();
        $sc = $store->get('school', false);
        $digits = '0123456789';
        $randomDigits = '';
        for ($i=0; $i<$n; $i++) {
            $randomDigits .= $digits[rand(0, 9)];
        }
        $code = 'G0X' . $id . '-Y' . $randomDigits . '@' . $sc['code'] . '-C1';
        return $code;
    }

    public function _generateStudentCode($n, $id)
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

    public function _storeChildren($guardian, $records)
    {
        foreach ($records as $record) {
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
            $student->guardian_id = $guardian->id;
            $student->guardian_name = $guardian->name;
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
            $student->save();
            $student->subjects()->saveMany(
                array_map(function ($subject) use ($student) {
                    return new StudentSubject([
                        'subject_id' => $subject['id'],
                        'academic_year_id' => $student->academic_year_id
                    ]);
                }, $record['subjects'])
            );
            $student_code = new StudentCode();
            $student_code->code = $this->_generateStudentCode(7, $student->id);
            $student_code->student_id = $student->id;
            $student_code->save();
        }
    }

    public function _attachChildren($guardian, $records)
    {
        foreach ($records as $record) {
            $student = Student::find($record['id']);
            $student->guardian_id = $guardian->id;
            $student->guardian_name = $guardian->name;
            $student->save();
        }
    }

    public function _emailLoginDetails($name, $email, $code, $schoolCID)
    {
        $store = new JsonStorage();
        $sc = $store->get('school', false);

        if ($sc["settings"]["offline_mode"] == 0) {
            $school_name = $sc['name'];
            $school_code = $sc['code'];
            $school_email = $sc['email'];

            $year = date('Y');
            $to = [$email, $name];
            $subject = 'Cilantro Mobile Setup [Guardian]';
            $title = 'Guardian Account Setup';
            $subtitle = 'Use your login credentials below and follow the instructions to complete the setup';

            $body = 'School CID: ' . $schoolCID . ', User Code: ' . $code;
            $body .= '<br />';
            $body .= '<br />';
            $body .= '<div>';
            $body .= '<ul style="list-style-type: disc;">';
            //$body .= '<li> Click on the appropriate link and install Cilantro mobile app: <a href="' . $sc['mobile']['android_download_link'] . '" style="color: #1a73e8; text-decoration: none;">' . $sc['mobile']['android_download_link'] . ' (android)</a>, or <a href="' . $sc['mobile']['ios_download_link'] . '" style="color: #1a73e8; text-decoration: none;">' . $sc['mobile']['ios_download_link'] . ' (ios)</a> </li>';
            $body .= '<li> Download and install Cilantro mobile app:  <a href="https://pryme-industries.com/download" style="color: #1a73e8; text-decoration: none;">https://pryme-industries.com/download</a> </li>';
            $body .= '<li> Open the installed app and sign in with your credentials above. </li>';
            $body .= '<li> Next, press the drawer icon at the top left corner and open settings. Open help, and read on how to use your account. </li>';
            $body .= '</ul>';
            $body .= '</div>';

            $data = ['title' => $title, 'subtitle' => $subtitle, 'name' => $to[1], 'body' => $body, 'school' => $school_name, 'code' => $school_code, 'email' => $school_email, 'year' => $year, 'showTitle' => true, 'showSubtitle' => true];

            if ($to[0] != null && $to[0] != '') {
                Mail::send('mail', $data, function ($message) use ($to, $subject) {
                    $message->to($to[0], $to[1])->subject($subject);
                });
            }
        }

    }





}
