<?php

namespace App\Http\Controllers;

use App\Models\GuardianCode;
use App\Packages\JsonStorage\JsonStorage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

class GuardianCodeController extends Controller
{
    public function index()
    {
        $guardian_codes = GuardianCode::with('guardian')->get();
        return response()->json($guardian_codes);
    }

    public function show($id)
    {
        $guardian_code = GuardianCode::with('guardian')->where('guardian_id', $id)->first();
        return response()->json($guardian_code);
    }


    public function validateGuardian($id)
    {
        $guardian_code = GuardianCode::with('guardian')->where('guardian_id', $id)->first();
        if ($guardian_code) {
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

            $guardian_code['guardian']['mobile'] = $sc['mobile'];

            return response()->json([
                'status' => 'success',
                'data' => $guardian_code["guardian"]
            ]);

        } else {
             return response()->json([
                'status' => 'error',
                'message' => 'Guardian not found.'
            ], 500);
        }
    
    }



}
