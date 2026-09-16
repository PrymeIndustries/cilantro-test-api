<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Packages\JsonStorage\JsonStorage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class LicenseController extends Controller
{
    public function __construct()
    {
    }

    public function setup_license(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Contact Us.'
        ]);
    }

    public function verify_license()
    {
        $store = new JsonStorage();
        $lc = $store->get('license', false);
        $active = false;
        $expires = null;
        $expires_in = null;
        $today = Carbon::now();
        $client = null;
        $end_date = null;
        if ($lc) {
            if (isset($lc['expires'])) {
                try {
                    $decrypted = Crypt::decryptString($lc['expires']);
                    $expiration = Carbon::parse($decrypted);
                    $active = $expiration->isAfter($today);
                    $expires = $expiration->format('Y-m-d');
                    $expires_in = $today->diffInDays($expiration, false);
                    $client = $lc['client'];
                    $end_date = $lc['end_date'];
                } catch (DecryptException | InvalidFormatException $e) {
                    return response()->json([
                        'status' => 'tempered license',
                        'message' => "Invalid license. Please, fetch your license again.",
                        'license' => $lc
                    ], 502);
                }
            }
        }
        
        if (!$active) {
            return response()->json([
                'status' => 'expired license',
                'message' => ("You license has expired (since: " . $expires . "). Please, renew your license to continue using " . env('APP_NAME')),
                'license' => $lc
            ], 502);
        }

        return [
            'status' => 'success',
            'active' => true,
            'expiration_date' => $expires,
            'expires_in' => $expires_in,
            'end_date' => $end_date,
            'now' => Carbon::now(),
            'client' => $client
        ];
    }



}
