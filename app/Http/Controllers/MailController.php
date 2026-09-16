<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use App\Packages\JsonStorage\JsonStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;

class MailController extends Controller
{
    public function sendEmail(Request $request): JsonResponse
    {
        $this->validate($request, [
            'to' => ['required', 'array', 'min:2'],
            'subject' => ['required', 'string'],
            'body' => ['required', 'string'],
            'attachments' => ['nullable', 'array']
        ]);

        $to = $request->input('to');
        $subject = $request->input('subject');
        $attachments = $request->input('attachments');

        $store = new JsonStorage();
        $sc = $store->get('school', false);
        $school_name = $sc['name'];
        $school_code = $sc['code'];
        $school_email = $sc['email'];

        $year = date('Y');
        $title = $subject;
        $subtitle = '';
        $body = $request->input('body');

        $show_title = false;
        $show_subtitle = false;
        
        if ($subject == 'Cilantro Mobile Setup [Teacher]' || $subject == 'Cilantro Mobile Setup [Guardian]') {
            $show_subtitle = true;
            $subtitle = 'Use your login credentials below and follow the instructions to complete the setup';
            $body .= '<br />';
            $body .= '<br />';
            $body .= '<div>';
            $body .= '<ul style="list-style-type: disc;">';
            //$body .= '<li> Click on the appropriate link and install Cilantro mobile app: <a href="' . $sc['mobile']['android_download_link'] . '" style="color: #1a73e8; text-decoration: none;">' . $sc['mobile']['android_download_link'] . ' (android)</a>, or <a href="' . $sc['mobile']['ios_download_link'] . '" style="color: #1a73e8; text-decoration: none;">' . $sc['mobile']['ios_download_link'] . ' (ios)</a> </li>';
            $body .= '<li> Download and install Cilantro mobile app:  <a href="https://pryme-industries.com/download" style="color: #1a73e8; text-decoration: none;">https://pryme-industries.com/download</a> </li>';
            $body .= '<li> Open the installed app and sign in with your credentials above. </li>';
            
            $tg = $request->input('tg') ? $request->input('tg') : '';
            if ($tg == 'Teacher') {
                $show_title = true;
                $title = 'Teacher Account Setup';
                $body .= '<li> Next, press the drawer icon at the top left corner and open settings. Open help, and read on how to use your account. </li>';
            } else if ($tg == 'Guardian') {
                $show_title = true;
                $title = 'Guardian Account Setup';
                $body .= '<li> Next, press the drawer icon at the top left corner and open settings. Open help, and read on how to use your account. </li>';
            }
            
            $body .= '</ul>';
            $body .= '</div>';
        }

        $data = ['title' => $title, 'subtitle' => $subtitle, 'name' => $to[1], 'body' => $body, 'school' => $school_name, 'code' => $school_code, 'email' => $school_email, 'year' => $year, 'showTitle' => $show_title, 'showSubtitle' => $show_subtitle];

        if ($to[0] != null && $to[0] != '') {
            Mail::send('mail', $data, function ($message) use ($to, $subject, $attachments) {
                $message->to($to[0], $to[1])->subject($subject);
                if ($attachments && $attachments != []) {
                    foreach ($attachments as $filepath) {
                        $message->attach($filepath);
                    }
                }
            });

            $active_ay = AcademicYear::where('active', true)->first();

            AuditLog::create([
                'user_id' => request()->user()->id ?? 0,
                'action' => 'SEND_EMAIL',
                'auditable_type' => 'Mail',
                'auditable_id' => 0,
                'description' => "Email sent to {$to[1]} with subject {$subject}",
                'old_values' => null,
                'new_values' => json_encode(['to' => $to, 'subject' => $subject, 'body' => $body]),
                'ip_address' => request()->ip(),
                'academic_year_id' => $active_ay?->id ?? 0
            ]);

        } else {
            return response()->json([
                'status' => 'error'
            ], 500);
        }

        return response()->json([
            'status' => 'success'
        ]);

    }


    public function sendEmailList(Request $request)
    {
        $records = $request->records;
        foreach ($records as $record){
            $errors = [];
            if (!isset($record['to'])) {
                $errors[] = 'To field is required';
            } else if (!isset($record['subject'])) {
                $errors[] = 'Subject field is required';
            } else if (!isset($record['body'])) {
                $errors[] = 'Body field is required';
            } else if (!isset($record['attachments'])) {
                $errors[] = 'Attachments field is required';
            }
            //
            if (count($errors) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => $errors[count($errors) - 1]
                ], 522);
            }
            
            $to = $record['to'];
            $subject = $record['subject'];
            $attachments = $record['attachments'];
            
            $store = new JsonStorage();
            $sc = $store->get('school', false);
            $school_name = $sc['name'];
            $school_code = $sc['code'];
            $school_email = $sc['email'];

            $year = date('Y');
            $title = $subject;
            $subtitle = '';
            $body = $record['body'];

            $show_title = false;
            $show_subtitle = false;

            if ($subject == 'Cilantro Mobile Setup [Teacher]' || $subject == 'Cilantro Mobile Setup [Guardian]') {
                $show_subtitle = true;
                $subtitle = 'Use your login credentials below and follow the instructions to complete the setup';
                $body .= '<br />';
                $body .= '<br />';
                $body .= '<div>';
                $body .= '<ul style="list-style-type: disc;">';
                //$body .= '<li> Click on the appropriate link and install Cilantro mobile app: <a href="' . $sc['mobile']['android_download_link'] . '" style="color: #1a73e8; text-decoration: none;">' . $sc['mobile']['android_download_link'] . ' (android)</a>, or <a href="' . $sc['mobile']['ios_download_link'] . '" style="color: #1a73e8; text-decoration: none;">' . $sc['mobile']['ios_download_link'] . ' (ios)</a> </li>';
                $body .= '<li> Download and install Cilantro mobile app:  <a href="https://pryme-industries.com/download" style="color: #1a73e8; text-decoration: none;">https://pryme-industries.com/download</a> </li>';
                $body .= '<li> Open the installed app and sign in with your credentials above. </li>';
                
                $tg = $record['tg'] ? $record['tg'] : '';
                if ($tg == 'Teacher') {
                    $show_title = true;
                    $title = 'Teacher Account Setup';
                    $body .= '<li> Next, press the drawer icon at the top left corner and open settings. Open help, and read on how to use your account. </li>';
                } else if ($tg == 'Guardian') {
                    $show_title = true;
                    $title = 'Guardian Account Setup';
                    $body .= '<li> Next, press the drawer icon at the top left corner and open settings. Open help, and read on how to use your account. </li>';
                }
                
                $body .= '</ul>';
                $body .= '</div>';
            }

            $data = ['title' => $title, 'subtitle' => $subtitle, 'name' => $to[1], 'body' => $body, 'school' => $school_name, 'code' => $school_code, 'email' => $school_email, 'year' => $year, 'showTitle' => $show_title, 'showSubtitle' => $show_subtitle];
            
            if ($to[0] != null && $to[0] != '') {
                Mail::send('mail', $data, function ($message) use ($to, $subject, $attachments) {
                    $message->to($to[0], $to[1])->subject($subject);
                    if ($attachments && $attachments != []) {
                        foreach ($attachments as $filepath) {
                            $message->attach($filepath);
                        }
                    }
                });

                $active_ay = AcademicYear::where('active', true)->first();
                
                AuditLog::create([
                    'user_id' => request()->user()->id ?? 0,
                    'action' => 'SEND_EMAIL_LIST',
                    'auditable_type' => 'Mail',
                    'auditable_id' => 0,
                    'description' => "Email sent to {$to[1]} with subject {$subject}",
                    'old_values' => null,
                    'new_values' => json_encode(['to' => $to, 'subject' => $subject, 'body' => $body]),
                    'ip_address' => request()->ip(),
                    'academic_year_id' => $active_ay?->id ?? 0
                ]);
                
            } else {
                return response()->json([
                    'status' => 'error'
                ], 500);
            }

        }

        return response()->json([
            'status' => 'success'
        ]);

    }





}
