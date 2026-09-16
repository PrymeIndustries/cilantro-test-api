<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Packages\JsonStorage\JsonStorage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get();
        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::find($id);
        return response()->json($user);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required',
                'username' => 'required',
                'email' => 'required',
                'password' => 'required|min:6'
            ]);
            $user = new User();
            $user->name = $request->input('name');
            $user->username = $request->input('username');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));

            if ($request->photo) {
                $user->photo = $request->photo;
                $user->photo_thumbnail = $request->photo;
            }

            $user->save();

            $ay_id = $request->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'CREATE_USER',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'description' => 'User ' . $user->name . ' created',
                'old_values' => null,
                'new_values' => json_encode($user),
                'ip_address' => $request->ip(),
                'academic_year_id' => $ay_id
            ]);

            return response()->json($user);
        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'name' => 'required',
                'username' => 'required',
                'email' => 'required',
            ]);
            $user = User::where('id', '=', $id)->first();
            $old_user = $user->replicate();
            $user->name = $request->input('name');
            $user->username = $request->input('username');
            $user->email = $request->input('email');

            if ($user->update()) {
                if ($request->photo) {
                    $user->photo = $request->photo;
                    $user->photo_thumbnail = $request->photo;
                    $user->update();
                }
            }
            $user->update();

            $ay_id = $request->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'UPDATE_USER',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'description' => 'User ' . $user->name . ' updated',
                'old_values' => json_encode($old_user),
                'new_values' => json_encode($user),
                'ip_address' => $request->ip(),
                'academic_year_id' => $ay_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $user->refresh()
            ]);
        });
        

    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $user = User::find($id);
            if ($user->system) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'System users cannot be deleted.'
                ], 522);
            }
            
            $user->delete();

            $ay_id = request()->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => request()->user()->id ?? 0,
                'action' => 'DELETE_USER',
                'auditable_type' => User::class,
                'auditable_id' => $id,
                'description' => 'User ' . $user->name . ' deleted',
                'old_values' => json_encode($user),
                'new_values' => null,
                'ip_address' => request()->ip(),
                'academic_year_id' => $ay_id
            ]);

            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }


    public function changePassword($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'old_password' => 'required|min:6',
                'password' => 'required|min:6',
                'password_confirmation' => 'required|min:6',
            ]);
            $user = User::find($id);
            if ($user && $user->id) {
                if ($request->password == $request->password_confirmation) {
                    if (Hash::check($request->old_password, $user->password)) {
                        $user->password = Hash::make($request->password);
                        $user->update();

                        $ay_id = $request->input('academic_year_id');
                        if (!$ay_id) {
                            $ay = AcademicYear::where('active', true)->first();
                            $ay_id = $ay ? $ay->id : 0;
                        }
                        AuditLog::create([
                            'user_id' => $request->user()->id ?? 0,
                            'action' => 'UPDATE_PASSWORD',
                            'auditable_type' => User::class,
                            'auditable_id' => $user->id,
                            'description' => 'User ' . $user->name . ' updated their password',
                            'old_values' => null,
                            'new_values' => null,
                            'ip_address' => $request->ip(),
                            'academic_year_id' => $ay_id
                        ]);

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Password has been updated.'
                        ]);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Old password is incorrect.'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Passwords do not match.'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User record not deleted!'
                ]);
            }
        });
        
    }

    public function resetPassword($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'password' => 'required|min:6'
            ]);
            $user = User::find($id);
            if ($user && $user->id) {
                $user->password = Hash::make($request->password);
                $user->update();

                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'RESET_PASSWORD',
                    'auditable_type' => User::class,
                    'auditable_id' => $user->id,
                    'description' => 'Password reset for user ' . $user->name,
                    'old_values' => null,
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'User password reset.'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User record not deleted!'
                ]);
            }
        });
        
    }

    public function changeStatus($id)
    {
        return DB::transaction(function () use ($id) {
            $user = User::find($id);
            if ($user) {
                $user->active = !$user->active;
                $user->save();

                $ay_id = request()->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => request()->user()->id ?? 0,
                    'action' => 'CHANGE_USER_STATUS',
                    'auditable_type' => User::class,
                    'auditable_id' => $user->id,
                    'description' => 'User ' . $user->name . ' status changed to ' . ($user->active ? 'Active' : 'Inactive'),
                    'old_values' => json_encode(['active' => !$user->active]),
                    'new_values' => json_encode(['active' => $user->active]),
                    'ip_address' => request()->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $user->refresh()
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User record not found!'
                ]);
            }
        });
        
    }

    public function changeLanguage($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'lang' => 'required|string'
            ]);
            $user = User::find($id);
            $user->lang = $request->input('lang');
            $user->update();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Language changed successfully.'
            ]);
        });
        
    }



    public function cdeLogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
            'server_api' => 'required'
        ]);
        
        $email = $request->input('email');
        $server_api = $request->input('server_api');
        $store = new JsonStorage();
        $license = $store->get('license', false);
        $today = Carbon::now();

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 522);
        }

        if (!$user->system) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied!'
            ], 522);
        }

        $api = env('APP_URL');
        if ($server_api != $api) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid server api entered, no server found. Please correct the server api.'
            ], 522);
        }

        if (!$user->active) {
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

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }


    public function c0Login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required',
            'server_api' => 'required'
        ]);
        
        $email = $request->input('email');
        $password = $request->input('password');
        $server_api = $request->input('server_api');
        $store = new JsonStorage();
        $license = $store->get('license', false);
        $today = Carbon::now();

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 522);
        }
        $api = env('APP_URL');
        if ($server_api != $api) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid server api entered, no server found. Please correct the server api.'
            ], 522);
        }

        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credentials are incorrect. Check your password.'
            ], 522);
        }

        if (!$user->active) {
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

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }



}
