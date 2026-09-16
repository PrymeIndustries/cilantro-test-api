<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Teacher;
use App\Models\TeacherCode;
use App\Models\TeacherSubject;
use App\Models\Message;
use App\Models\Subject;
use App\Models\SubjectPeriod;
use App\Models\Submission;
use App\Models\StudentMark;
use App\Models\AuditLog;
use App\Packages\JsonStorage\JsonStorage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::all();
        return response()->json($teachers);
    }

    public function getTeachers($ay_id)
    {
        $teachers = Teacher::where(function ($query) use ($ay_id) {
                            $query->where('academic_year_id', $ay_id)
                                  ->orWhere('active', true);
                            })->get();
        return response()->json($teachers);
    }

    public function show($id)
    {
        $teacher = Teacher::find($id);
        return response()->json($teacher);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
                'telephone' => 'required|min:6',
                'gender' => 'required'
            ]);
            $teacher = new Teacher();
            $teacher->first_name = $request->input('first_name');
            $teacher->last_name = $request->input('last_name');
            $teacher->matricule = $request->input('matricule');
            $teacher->email = $request->input('email');
            $teacher->telephone = $request->input('telephone');
            $teacher->gender = $request->input('gender');
            $teacher->address = $request->input('address');

            if ($request->photo) {
                $teacher->photo = $request->photo;
                $teacher->photo_thumbnail = $request->photo;
            }

            $ay_id = $request->input('academic_year_id');
            if ($ay_id) {
                $teacher->academic_year_id = $ay_id;
            } else {
                $active_ay = AcademicYear::where('active', true)->first();
                $teacher->academic_year_id = $active_ay->id;
            }

            $teacher->save();

            $teacher->subjects()->saveMany(
                array_map(function ($subject) use ($teacher) {
                    return new TeacherSubject([
                        'subject_id' => $subject['id'],
                        'academic_year_id' => $teacher->academic_year_id
                    ]);
                }, $request->subjects)
            );

            $teacher_code = new TeacherCode();
            $teacher_code->code = $this->_generateCode(10, $teacher->id);
            $teacher_code->teacher_id = $teacher->id;
            $teacher_code->save();

            if ($teacher->email != null && $teacher->email != '') {
                $name = $teacher->first_name . ' ' . $teacher->last_name;
                $this->_emailLoginDetails($name, $teacher->email, $teacher_code->code, $request->input('schoolCID'));
            }

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'CREATE_TEACHER',
                'auditable_type' => Teacher::class,
                'auditable_id' => $teacher->id,
                'description' => 'Teacher ' . $teacher->first_name . ' ' . $teacher->last_name . ' created',
                'old_values' => null,
                'new_values' => json_encode($teacher),
                'ip_address' => $request->ip(),
                'academic_year_id' => $teacher->academic_year_id
            ]);

            return response()->json($teacher);
        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
                'telephone' => 'required',
                'gender' => 'required'
            ]);
            $teacher = Teacher::where('id', $id)->first();
            $old_teacher = $teacher->replicate();
            $teacher->first_name = $request->input('first_name');
            $teacher->last_name = $request->input('last_name');
            $teacher->matricule = $request->input('matricule');
            $teacher->email = $request->input('email');
            $teacher->telephone = $request->input('telephone');
            $teacher->gender = $request->input('gender');
            $teacher->address = $request->input('address');

            if ($request->input('update_subjects')) {
                $new_subjects = $request->input('subject_ids');
                $old_subjects = TeacherSubject::where('teacher_id', $teacher->id)->get();
                foreach ($old_subjects as $subject) {
                    $subject->delete();
                }
                foreach ($new_subjects as $subject_id) {
                    $teacher_subject = new TeacherSubject();
                    $teacher_subject->teacher_id = $teacher->id;
                    $teacher_subject->subject_id = $subject_id;
                    $teacher_subject->academic_year_id = $teacher->academic_year_id;
                    $teacher_subject->save();
                }
            }

            if ($teacher->update()) {
                if ($request->photo) {
                    $teacher->photo = $request->photo;
                    $teacher->photo_thumbnail = $request->photo;
                    $teacher->update();
                }
            }
            $teacher->update();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'UPDATE_TEACHER',
                'auditable_type' => Teacher::class,
                'auditable_id' => $teacher->id,
                'description' => 'Teacher ' . $teacher->first_name . ' ' . $teacher->last_name . ' updated',
                'old_values' => json_encode($old_teacher),
                'new_values' => json_encode($teacher),
                'ip_address' => $request->ip(),
                'academic_year_id' => $teacher->academic_year_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $teacher->refresh()
            ]);
        });
        

    }

    public function updateList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $errors = [];
                if (!isset($record['first_name'])) {
                    $errors[] = 'First name field is required';
                }
                if (!isset($record['last_name'])) {
                    $errors[] = 'Last name field is required';
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
                
                $teacher = Teacher::where('id', $record['id'])->first();
                $teacher->academic_year_id = $record['academic_year_id'];

                $subjects = TeacherSubject::where('teacher_id', $teacher->id)->get();
                foreach ($subjects as $subject) {
                    $subject->academic_year_id = $record['academic_year_id'];
                    $subject->save();
                }

                $teacher->save();
            }
            
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $submissions = Submission::where('teacher_id', $id)->get();
            if (count($submissions) == 0) {
                $teacher = Teacher::find($id);
                $teacher_code = TeacherCode::where('teacher_id', $id)->first();
                $subjects = TeacherSubject::where('teacher_id', $id)->get();
                $teacher->delete();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'DELETE_TEACHER',
                    'auditable_type' => Teacher::class,
                    'auditable_id' => $id,
                    'description' => 'Teacher ' . $teacher->first_name . ' ' . $teacher->last_name . ' deleted',
                    'old_values' => json_encode($teacher),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $teacher->academic_year_id
                ]);

                $teacher_code->delete();
                foreach ($subjects as $subject) {
                    $subject->delete();
                }
                return response()->json([
                    'status' => 'success'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete this teacher'
                ], 522);
            }
        });
        
    }

    public function changeStatus($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $teacher = Teacher::find($id);
            if ($teacher) {
                $teacher->active = !$teacher->active;
                if ($teacher->active) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    $teacher->academic_year_id = $active_ay->id;
                }
                $teacher->save();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CHANGE_TEACHER_STATUS',
                    'auditable_type' => Teacher::class,
                    'auditable_id' => $teacher->id,
                    'description' => 'Teacher ' . $teacher->first_name . ' ' . $teacher->last_name . ' status changed to ' . ($teacher->active ? 'Active' : 'Inactive'),
                    'old_values' => json_encode(['active' => !$teacher->active]),
                    'new_values' => json_encode(['active' => $teacher->active]),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $teacher->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $teacher->refresh()
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Teacher record not found!'
                ]);
            }
        });
        
    }


    public function getSubjectsWithPeriods($id)
    {
        $subjects = Subject::with('category')->get();
        $subject_periods = SubjectPeriod::with('subject')
                                ->where('teacher_id', $id)
                                ->get();
        foreach ($subjects as $subject) {
            $periods = [];
            foreach ($subject_periods as $subject_period) {
                if ($subject_period->subject_id == $subject->id) {
                    $periods[] = $subject_period;
                }
            }
            $subject['periods'] = $periods;
        }
        return response()->json($subjects);
    }

    public function getCurrentSubjects($id)
    {
        $active_ay = AcademicYear::where('active', true)->first();
        $subjects = TeacherSubject::with('subject')
                                ->where('teacher_id', $id)
                                ->where('academic_year_id', $active_ay->id)
                                ->get();
        return response()->json($subjects);
    }

    public function getSubmissions($id, Request $request)
    {
        $submissions = Submission::with(['teacher', 'submission_type'])
                                ->where('academic_year_id', $request->academic_year_id)
                                ->where('teacher_id', $id)
                                ->get();
        return response()->json($submissions);
    }

    public function getSubjectSubmissionMarks($id, Request $request)
    {
        $teacher_code = TeacherCode::where('teacher_id', $id)->first();
        $teacher_id = $teacher_code ? $teacher_code->teacher_id : null;
        $subject = $request->subject;
        $lang = $request->lang ? $request->lang : 'en';
        if ($teacher_id != null) {
            $submissions = Submission::with(['teacher', 'submission_type'])
                                ->where('teacher_id', $teacher_id)
                                ->where('subject_id', $subject["id"])
                                ->get();
            $records = [];
            foreach ($submissions as $submission) {
                $students_marks = StudentMark::with(['student', 'subject', 'submission', 'submission_type'])
                                    ->where('submission_id', $submission->id)
                                    ->get();
                
                $html_table = "<br/>";
                $html_table .= "<h2 style='text-align: center;'> " . $subject["name"] . ' ' . '-' . ' ' . $submission->submission_type["name"] . ' ' . '/' . $submission->total_marks . ' ' . "(" . $this->_translateText($submission->status, $lang) . ")" . " <h2>";
                $html_table .= "<br/>";
                $html_table .= "<table style='width: 100%; border: 1px solid black; border-collapse: collapse;'>";
                $html_table .= "<tr>";
                $html_table .= "<th style='text-align: left; padding-top: 10px; padding-bottom: 10px; padding-left: 10px; border: 1px solid black; border-collapse: collapse;'> S/N </th>";
                $html_table .= "<th style='text-align: left; padding-top: 10px; padding-bottom: 10px; padding-left: 10px; border: 1px solid black; border-collapse: collapse;'> " . $this->_translateText('Student', $lang) . " </th>";
                $html_table .= "<th style='text-align: left; padding-top: 10px; padding-bottom: 10px; padding-left: 10px; border: 1px solid black; border-collapse: collapse;'> " . $this->_translateText('Mark', $lang) . " </th>";
                $html_table .= "</tr>";
                $index = 1;
                foreach ($students_marks as $student_mark) {
                    $html_table .= "<tr>";
                    $html_table .= "<td style='padding-top: 5px; padding-bottom: 5px; padding-left: 10px; text-align: left; border: 1px solid black; border-collapse: collapse;'> " . $index . " </td>";
                    $html_table .= "<td style='padding-top: 5px; padding-bottom: 5px; padding-left: 10px; text-align: left; border: 1px solid black; border-collapse: collapse;'> " . $student_mark->student["full_name"] . " </td>";
                    $html_table .= "<td style='padding-top: 5px; padding-bottom: 5px; padding-left: 10px; text-align: left; border: 1px solid black; border-collapse: collapse;'> " . $student_mark->marks_obtained . " </td>";
                    $html_table .= "</tr>";
                    $index = $index + 1;
                }
                $html_table .= "</table>";
                $record = ['submission' => $submission, 'subject' => $subject, 'table' => $html_table];
                $records[] = $record;
            }

            $records = array_reverse($records);
            

            return response()->json($records);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Teacher not found.'
            ], 522);
        }
    }

    public function getMessages($code)
    {
        $data = Message::latest()->cursor();
        $messages = [];
        foreach ($data as $msg) {
            if (isset($msg->to[0]) && ($msg->to[0] == $code || $msg->to[0] == 'T0X')) {
                $messages[] = $msg;
            }
        }
        return response()->json($messages);
    }

    

    public function search(Request $request)
    {
        $query = $request->name;
        $results = Teacher::where('first_name', 'like', '%' . $query . '%')
                    ->orWhere('last_name', 'like', '%' . $query . '%')
                    ->get();
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
        $school_code = $request->input('school_code');
        $server_api = $request->input('server_api');
        $store = new JsonStorage();
        $school = $store->get('school', false);
        $license = $store->get('license', false);
        $today = Carbon::now();

        $teacher_code = TeacherCode::with('teacher')
                ->cursor()
                ->filter(fn ($item) => $item->code == $code)
                ->first();

        if (!$teacher_code || ($teacher_code->code != $code)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid code entered, no teacher found. Please correct your code.'
            ], 522);
        }
        
        $api = env('APP_URL');
        if ($server_api != $api) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid server api entered, no server found. Please correct the server api.'
            ], 522);
        }

        if (!$teacher_code->teacher['active']) {
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

        $teacher_code['teacher']['school_code'] = $school['code'];
        $teacher_code['teacher']['mobile'] = $school['mobile'];
        $teacher_code['teacher']['app_version'] = env('APP_VERSION');

        return response()->json([
            'status' => 'success',
            'data' => $teacher_code['teacher']
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
        $code = 'T0X' . $id . '-Y' . $randomDigits . '@' . $sc['code'] . '-C1';
        return $code;
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
            $subject = 'Cilantro Mobile Setup [Teacher]';
            $title = 'Teacher Account Setup';
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


    public function _translateText($text, $lang)
    {
        $translated_text = '';
        $lower_text = strtolower($text);
        if ($lang == 'en') {
            if ($lower_text == 'student') {
                $translated_text = 'Student';
            } else if ($lower_text == 'mark') {
                $translated_text = 'Mark';
            } else if ($lower_text == 'approved') {
                $translated_text = 'Approved';
            } else if ($lower_text == 'pending') {
                $translated_text = 'Pending';
            } else if ($lower_text == 'declined') {
                $translated_text = 'Declined';
            }
        } else if ($lang == 'fr') {
            if ($lower_text == 'student') {
                $translated_text = 'Étudiant';
            } else if ($lower_text == 'mark') {
                $translated_text = 'Note';
            } else if ($lower_text == 'approved') {
                $translated_text = 'Approuvé';
            } else if ($lower_text == 'pending') {
                $translated_text = 'En attente';
            } else if ($lower_text == 'declined') {
                $translated_text = 'Refusé';
            }
        } else {
            $translated_text = $text;
        }
        return $translated_text;
    }




}
