<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh', 'logout']]);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['username', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        AuditLog::create([
            'user_id' => auth()->user()->id ?? 0,
            'action' => 'LOGIN',
            'auditable_type' => 'User',
            'auditable_id' => auth()->user()->id ?? 0,
            'description' => 'User logged in',
            'old_values' => null,
            'new_values' => null,
            'ip_address' => request()->ip(),
            'academic_year_id' => 0
        ]);

        return $this->jsonResponse($token);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        AuditLog::create([
            'user_id' => auth()->user()->id ?? 0,
            'action' => 'LOGOUT',
            'auditable_type' => 'User',
            'auditable_id' => auth()->user()->id ?? 0,
            'description' => 'User logged out',
            'old_values' => null,
            'new_values' => null,
            'ip_address' => request()->ip(),
            'academic_year_id' => 0
        ]);

        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->jsonResponse(auth()->refresh());
    }

    protected function jsonResponse($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'user'         => auth()->user(),
            'expires_in'   => auth()->factory()->getTTL() * 60
        ]);
    }



}
