<?php

namespace App\Http\Controllers;

use App\Models\StudentCode;
use Illuminate\Http\Request;

class StudentCodeController extends Controller
{
    public function index()
    {
        $student_codes = StudentCode::with('student')->get();
        return response()->json($student_codes);
    }

    public function show($id)
    {
        $student_code = StudentCode::with('student')->where('student_id', $id)->first();
        return response()->json($student_code);
    }


}
