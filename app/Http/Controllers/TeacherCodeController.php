<?php

namespace App\Http\Controllers;

use App\Models\TeacherCode;
use App\Packages\JsonStorage\JsonStorage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

class TeacherCodeController extends Controller
{

    public function index()
    {
        $teacher_codes = TeacherCode::with('teacher')->get();
        return response()->json($teacher_codes);
    }

    public function show($id)
    {
        $teacher_code = TeacherCode::with('teacher')->where('teacher_id', $id)->first();
        return response()->json($teacher_code);
    }


    public function validateTeacher($id)
    {
        $teacher_code = TeacherCode::with('teacher')->where('teacher_id', $id)->first();
        if ($teacher_code) {
            $store = new JsonStorage();
            $sc = $store->get('school', false);
            $lc = $store->get('license', false);
            $today = Carbon::now();
            if ($lc) {
                if (isset($lc['expires'])) {
                    try {
                        $decrypted = Crypt::decryptString($lc['expires']);
                        $expiration = Carbon::parse($decrypted);
                        $active = $expiration->isAfter($today);
                        if (!$active) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Expired license.'
                            ], 502);
                        }
                    } catch (DecryptException | InvalidFormatException $e) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Invalid license."
                        ], 502);
                    }
                }
            }

            $teacher_code['teacher']['mobile'] = $sc['mobile'];

            return response()->json([
                'status' => 'success',
                'data' => $teacher_code["teacher"]
            ]);
        
        } else {
             return response()->json([
                'status' => 'error',
                'message' => 'Teacher not found.'
            ], 500);
        }
    
    }



}
