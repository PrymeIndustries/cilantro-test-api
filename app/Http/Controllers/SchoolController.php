<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AcademicYear;
use App\Packages\JsonStorage\JsonStorage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SchoolController extends Controller
{
    public function setup_school(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'code' => 'required',
            'email' => 'required|min:6',
            'phone1' => 'required|min:6',
            'phone2' => 'required|min:6',
            'country' => 'required',
            'currency' => 'required',
            'address' => 'required'
        ]);

        $store = new JsonStorage();
        $data = $request->all();

        if ($data['logo'] && $data['logo'] != '') {
            $logo = $data['logo'];
        }

        if ($data['signature']) {
            $signature = $data['signature'];
        } else {
            $signature = "";
        }

        if ($data['stamp']) {
            $stamp = $data['stamp'];
        } else {
            $stamp = "";
        }

        $store->set('school', [
            'name' => $data['name'],
            'code' => $data['code'],
            'motto' => $data['motto'],
            'section' => $data['section'],
            'email' => $data['email'],
            'phone 1' => $data['phone1'],
            'phone 2' => $data['phone2'],
            'country' => $data['country'],
            'currency' => $data['currency'],
            'address' => $data['address'],
            'language' => $data['language'],
            'website' => $data['website'],
            'logo' => $logo,
            'signature' => $signature,
            'stamp' => $stamp,
            'academic_year' => $data['academic_year'],
            'mobile' => $data['mobile'],
            'finance' => $data['finance'],
            'settings' => $data['settings']
        ]);
        $store->save();
        
        $ay_id = $request->input('academic_year_id');
        if (!$ay_id) {
            $ay = AcademicYear::where('active', true)->first();
            $ay_id = $ay ? $ay->id : 0;
        }
        AuditLog::create([
            'user_id' => $request->user() ? $request->user()->id : 0,
            'action' => 'UPDATE_SCHOOL_SETTINGS',
            'auditable_type' => '@School',
            'auditable_id' => 0,
            'description' => 'School settings updated',
            'old_values' => null,
            'new_values' => json_encode($data),
            'ip_address' => $request->ip(),
            'academic_year_id' => $ay_id
        ]);

        return [
            'status' => 'success',
            'data' => $store->get('school', [])
        ];
    }

    public function verify_school()
    {
        $store = new JsonStorage();
        $sc = $store->get('school', false);
        return response()->json([
            'status' => 'success',
            'data' => $sc
        ]);
    }

    public function getVersion()
    {
        $app_version = env('APP_VERSION');
        return $app_version;
    }

    public function getServerAPI()
    {
        $server_base_api = env('APP_URL');
        return $server_base_api;
    }

    public function getSettings()
    {
        $store = new JsonStorage();
        $sc = $store->get('school', false);
        return $sc['settings'];
    }

    public function getAcademicYears()
    {
        $years = AcademicYear::where('status', '!=', 'ready')->get();
        return response()->json(array_reverse($years->toArray()));
    }

    public function getCurrencyDetails()
    {
        $store = new JsonStorage();
        $sc = $store->get('school', false);
        return response()->json([
            'status' => 'success',
            'data' => [
                'currency' => $sc['currency'],
                'pos' => $sc['settings']['currency_position']
            ]
        ]);
    }

    public function getMobileAppDownloadLinks()
    {
        $store = new JsonStorage();
        $sc = $store->get('school', false);
        return response()->json([
            'status' => 'success',
            'data' => [
                'android_download_link' => $sc['mobile']['android_download_link'],
                'ios_download_link' => $sc['mobile']['ios_download_link']
            ]
        ]);
    }

    public function getPOSPrinterDetails()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => env('C_PRINTER_NAME') ? env('C_PRINTER_NAME') : 'C_Printer',
                'preview' => env('C_PRINTER_OPTION_PREVIEW') ? env('C_PRINTER_OPTION_PREVIEW') == 'true' ? true : false : false,
                'margin' => env('C_PRINTER_OPTION_MARGIN') ? env('C_PRINTER_OPTION_MARGIN') : "0 0 0 0",
                'copies' => env('C_PRINTER_OPTION_COPIES') ? (int)env('C_PRINTER_OPTION_COPIES') : 1,
                'time_out_per_line' => env('C_PRINTER_OPTION_TIMEOUTPERLINE') ? (int)env('C_PRINTER_OPTION_TIMEOUTPERLINE') : 400,
                'page_size' => env('C_PRINTER_OPTION_PAGESIZE') ? env('C_PRINTER_OPTION_PAGESIZE') : "80mm",
                'silent' => env('C_PRINTER_OPTION_PAGESIZE') ? env('C_PRINTER_OPTION_PAGESIZE') == 'true' ? true : false : true,
            ]
        ]);
    }



}
