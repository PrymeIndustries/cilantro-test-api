<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\ReportSheet;
use App\Packages\JsonStorage\JsonStorage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;



class _EinsteinController extends Controller
{
    public function info()
    {
        return "AI Controller: EINSTEIN (Enhanced-Intelligent-Nitid-System-Trained-Extensively-In-Niche)";
    }

    public function change_tricorder_status(Request $request) {
        $this->validate($request, [
            'enabled' => 'required'
        ]);
        $store = new JsonStorage();
        $data = $request->all();
        if ($data['enabled'] == 1 || $data['enabled'] == 0) {
            $enabled = $data['enabled'];
        } else {
            $enabled = 0;
        }
        $store->set('tricorder', [
            'enabled' => $enabled
        ]);
        $store->save();
        return [
            'status' => 'success',
            'data' => $store->get('tricorder', [])
        ];
    }

    public function renew_license(Request $request) {
        $years = $request->years ? $request->years : 1;
        $store = new JsonStorage();
        $lc = $store->get('license', false);
        try {
            $old_date = Crypt::decryptString($lc['expires']);
            $new_date = Carbon::parse($old_date)->addYears($years)->toDateString();
            $updated = $this->_setLicenseDate($new_date);
            if ($updated) {
                return response()->json([
                    'status' => 'success',
                    'data' => ['old_date' => $old_date, 'new_date' => $new_date]
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'License not renewed, it is probably still active.'
                ], 522);
            }
        } catch (DecryptException | InvalidFormatException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'License has been blocked, so it cannot be renewed.'
            ], 522);
        }
    }

    public function update_license($date) {
        $updated = $this->_setLicenseDate($date, true);
        if ($updated) {
            return response()->json([
                'status' => 'success',
                'message' => 'New license date set to ' . $date
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'License date not set.'
            ], 522);
        }
    }

    public function block_license() {
        $updated = $this->_setLicenseDate('yyy', true);
        if ($updated) {
            return response()->json([
                'status' => 'success',
                'message' => 'license blocked'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'License not blocked.'
            ], 522);
        }
    }

    public function create_new_ay(Request $request) {
        $years = $request->years ? $request->years : 1;
        $new_ays = AcademicYear::where('status', 'ready')
                    ->where('active', false)
                    ->where('temp_active', false)
                    ->get();
        if (count($new_ays) == 0) {
            $store = new JsonStorage();
            $sc = $store->get('school', false);
            $old_start_date = $sc['academic_year']['start_date'];
            $old_end_date = $sc['academic_year']['end_date'];
            $old_from_y = $sc['academic_year']['from_y'];
            $old_to_y = $sc['academic_year']['to_y'];
            try {
                $new_start_date = Carbon::parse($old_start_date)->addYears($years)->toDateString();
                $new_end_date = Carbon::parse($old_end_date)->addYears($years)->toDateString();
                $new_from_y = $old_from_y + $years;
                $new_to_y = $old_to_y + $years;
                $new_year = $new_from_y . ' / ' . $new_to_y;
                $this->_updateSchoolAY($new_year, $new_start_date, $new_end_date, $new_from_y, $new_to_y);
                
                // ays
                $ay = new AcademicYear();
                $ay->year = $new_year;
                $ay->status = 'ready';
                $ay->active = false;
                $ay->temp_active = false;
                $ay->save();
                
                // terms
                $terms = Term::all();
                foreach ($terms as $term) {
                    $term->start_date = Carbon::parse($term->start_date)->addYears($years)->toDateString();
                    $term->end_date = Carbon::parse($term->end_date)->addYears($years)->toDateString();
                    $updated_term = [
                        'name' => $term->name,
                        'start_date' => $term->start_date,
                        'end_date' => $term->end_date,
                        'active' => $term->active,
                        'final' => $term->final,
                        'created_at' => $term->created_at,
                        'updated_at' => $term->updated_at
                    ];
                    $term->update($updated_term);
                }
                
                //
                if ($sc["settings"]["delete_report_sheets"] == 1) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    $report_sheets = ReportSheet::where('academic_year_id', '!=', $active_ay->id)->get();
                    foreach ($report_sheets as $report_sheet) {
                        $report_sheet->delete();
                    }
                }
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'New academic year created, ' . $ay['year']
                ]);
            } catch (DecryptException | InvalidFormatException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to update dates.'
                ], 522);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'A new academic year is already ready.'
            ], 522);
        }
    }

    public function get_school_info() {
        $store = new JsonStorage();
        $sc = $store->get('school', false);
        $lc = $store->get('license', false);
        $tc = $store->get('tricorder', false);
        
        $today = Carbon::now();
        $decrypted = null;
        $expiration = null;
        $expires_in = null;

        try {
            $decrypted = Crypt::decryptString($lc['expires']);
            $expiration = Carbon::parse($decrypted);
            $expires_in = $today->diffInDays($expiration, false);
        } catch(DecryptException | InvalidFormatException $e) {}
        
        
        $active_ay = AcademicYear::where('active', true)->first();

        $users = User::where('system', false)->get();

        $teachers = Teacher::where(function ($query) use ($active_ay) {
                            $query->where('academic_year_id', $active_ay->id)
                                ->orWhere('active', true);
                        })->get();

        $students =  Student::where('academic_year_id', $active_ay->id)->get();

        $guardians = [];
        $all_guardians = Guardian::where('academic_year_id', $active_ay->id)->get();
        foreach ($all_guardians as $guardian) {
            $children = array_filter($students->toArray(), function ($student) use ($guardian) {
                return $student['guardian_id'] == $guardian->id;
            });
            if (count($children) > 0) {
                $guardians[] = $guardian;
            }
        }

        $payment = $this->_getPayment($sc, $sc["settings"]["student_duplicates"] != 1 ? count($students) : count($students)/2);
        
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'logo' => $sc['logo'],
                'name' => $sc['name'],
                'code' => $sc['code'],
                'motto' => $sc['motto'],
                'section' => $sc['section'],
                'email' => $sc['email'],
                'phone1' => $sc['phone 1'],
                'phone2' => $sc['phone 2'],
                'country' => $sc['country'],
                'currency' => $sc['currency'],
                'address' => $sc['address'],
                'language' => $sc['language'],
                'website' => $sc['website'],
                'number_of_users' => count($users),
                'number_of_teachers' => count($teachers),
                'number_of_students' => $sc["settings"]["student_duplicates"] != 1 ? count($students) : count($students)/2,
                'number_of_guardians' => count($guardians),
                'payment' => $payment,
                'aps' => $sc["finance"]["price_per_student"],
                'people' => [
                    'users' => $users,
                    'teachers' => $teachers,
                    'students' => $students,
                    'guardians' => $guardians
                ],
                'license' => [
                    'start_date' => $lc['start_date'],
                    'end_date' => $lc['end_date'],
                    'expiration_date' => $decrypted,
                    'expires_in' => $expires_in
                ],
                'tricorder_enabled' => $tc['enabled'],
                'app_version' => env('APP_VERSION')
            ]
        ]);
    }
    

    public function _getPayment($sc, $number_of_students ) {
        $price_per_student = $sc["finance"]["price_per_student"];
        $currency = $sc["currency"];
        $amount = $price_per_student * $number_of_students;
        return $currency . ' ' . $amount;
    }

    public function _updateSchoolAY($new_year, $new_start_date, $new_end_date, $new_from_y, $new_to_y)
    {
        $store = new JsonStorage();
        $sc = $store->get('school', false);
        $new_academic_year = [
            'year' => $new_year,
            'start_date' => $new_start_date,
            'end_date' => $new_end_date,
            'from_y' => $new_from_y,
            'to_y' => $new_to_y
        ];
        $store->set('school', [
            'name' => $sc['name'],
            'code' => $sc['code'],
            'motto' => $sc['motto'],
            'section' => $sc['section'],
            'email' => $sc['email'],
            'phone 1' => $sc['phone 1'],
            'phone 2' => $sc['phone 2'],
            'country' => $sc['country'],
            'currency' => $sc['currency'],
            'address' => $sc['address'],
            'language' => $sc['language'],
            'website' => $sc['website'],
            'logo' => $sc['logo'],
            'signature' => $sc['signature'],
            'stamp' => $sc['stamp'],
            'academic_year' => $new_academic_year,
            'mobile' => $sc['mobile'],
            'finance' => $sc['finance'],
            'settings' => $sc['settings']
        ]);
        $store->save();
    }

    public function _setLicenseDate($date, $force=false)
    {
        $store = new JsonStorage();
        $lc = $store->get('license', false);
        $active = false;
        $today = Carbon::now();
        if ($lc) {
            if (isset($lc['expires'])) {
                try {
                    $decrypted = Crypt::decryptString($lc['expires']);
                    $expiration = Carbon::parse($decrypted);
                    $active = $expiration->isAfter($today);
                    if (!$active || $force || 1==1) {
                        $new_encrypted_date = Crypt::encryptString($date);
                        $store->set('license', [
                            'start_date' => $lc['start_date'],
                            'end_date' => $date,
                            'expires' => $new_encrypted_date,
                            'client' => $lc['client']
                        ]);
                        $store->save();
                        return true;
                    }
                    return false;
                } catch (DecryptException | InvalidFormatException $e) {
                    if ($force) {
                        $new_encrypted_date = Crypt::encryptString($date);
                        $store->set('license', [
                            'start_date' => $lc['start_date'],
                            'end_date' => $date,
                            'expires' => $new_encrypted_date,
                            'client' => $lc['client']
                        ]);
                        $store->save();
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
        return false;
    }



}
