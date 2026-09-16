<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

use App\Models\PermissionGroup;
use App\Models\PermissionRole;
use App\Models\Role;
use App\Models\User;
use App\Models\RoleUser;
use App\Models\Permission;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Events\RoleSyncable;
use App\Events\UserSyncable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(Role::all());
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'head' => 'required'
            ]);

            $role = new Role();
            $role->name = $request->name;
            $role->slug = strtolower($role->name);
            $role->description = $request->description;
            $role->head = $request->head;

            if ($role->head) {
                $roles = Role::all();
                foreach ($roles as $r) {
                    $r->head = false;
                    $r->save();
                }
            }

            if ($role->save()) {
                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 0,
                    'action' => 'CREATE_ROLE',
                    'auditable_type' => Role::class,
                    'auditable_id' => $role->id,
                    'description' => 'Role ' . $role->name . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($role),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Role created successfully.'
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error while creating role!'
            ]);
        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'description' => 'required|string|max:255',
                'name' => "required|string|unique:roles,name,$id",
                'head' => 'required'
            ]);

            $role = Role::find($id);
            $old_role = $role->replicate();
            $role->name = $request->name;
            $role->slug = strtolower($role->name);
            $role->head = $request->head;

            if ($role->head) {
                $roles = Role::where('id', '!=', $id)->get();
                foreach ($roles as $r) {
                    $r->head = false;
                    $r->save();
                }
            }

            if ($role->update()) {
                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 0,
                    'action' => 'UPDATE_ROLE',
                    'auditable_type' => Role::class,
                    'auditable_id' => $role->id,
                    'description' => 'Role ' . $role->name . ' updated',
                    'old_values' => json_encode($old_role),
                    'new_values' => json_encode($role),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Role updated successfully.',
                    'role' => $role
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error while updating role!'
            ]);
        });
        
    }

    public function destroy($id, \Illuminate\Http\Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $role = Role::find($id);
            $exists = User::whereRoleId($id)->exists();
            if ($exists) {
                return response()->json([
                    'status' => 'error 2',
                    'message' => 'Role cannot be deleted because it is attached to a user.'
                ]);
            }
            if ($role->system) {
                return response()->json([
                    'status' => 'error 1',
                    'message' => 'System roles cannot be deleted.'
                ]);
            }
            if ($role && $role->id && $role->delete()) {
                $ay_id = $request->input('academic_year_id');
                if (!$ay_id) {
                    $ay = AcademicYear::where('active', true)->first();
                    $ay_id = $ay ? $ay->id : 0;
                }
                AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 0,
                    'action' => 'DELETE_ROLE',
                    'auditable_type' => Role::class,
                    'auditable_id' => $id,
                    'description' => 'Role ' . $role->name . ' deleted',
                    'old_values' => json_encode($role),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $ay_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Role deleted successfully deleted'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Role record not found!'
                ]);
            }
        });
        
    }


    public function assignUserRole($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $user = User::find($id);
            $user->role_id = $request->role_id;
    //        if ($request->role_id == 1) {
    //            $user->is_admin = 1;
    //        } else {
    //            $user->is_admin = 0;
    //        }
            $user->save();

            $ay_id = $request->input('academic_year_id');
            if (!$ay_id) {
                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;
            }
            AuditLog::create([
                'user_id' => $request->user() ? $request->user()->id : 0,
                'action' => 'ASSIGN_USER_ROLE',
                'auditable_type' => User::class,
                'auditable_id' => $id,
                'description' => 'Role assigned to user ' . $user->name,
                'old_values' => null,
                'new_values' => json_encode(['role_id' => $user->role_id]),
                'ip_address' => $request->ip(),
                'academic_year_id' => $ay_id
            ]);

            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function storeRolePermission(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'permission_id' => 'required',
                'role_id' => 'required'
            ]);

            $permission_role = new PermissionRole();
            $permission_role->permission_id = $request->permission_id;
            $permission_role->role_id = $request->role_id;
            $permission_role->created_at = null;
            $permission_role->updated_at = null;

            if ($permission_role->save()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'ADD_ROLE_PERMISSION',
                    'auditable_type' => Role::class, // PermissionRole::class
                    'auditable_id' => $request->role_id,
                    'description' => 'Permission ID ' . $request->permission_id . ' added to role ID ' . $request->role_id,
                    'old_values' => null,
                    'new_values' => json_encode($permission_role),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Permission added successfully.'
                ]);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Error while creating permission!'
            ]);
        });
        
    }

    public function destroyRolePermission($id)
    {
        return DB::transaction(function () use ($id) {
            $permission_role = PermissionRole::find($id);
            if ($permission_role) {
                $old_pr = $permission_role->replicate();
                if ($permission_role->delete()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => request()->user()->id ?? 0,
                        'action' => 'REMOVE_ROLE_PERMISSION',
                        'auditable_type' => Role::class,
                        'auditable_id' => $old_pr->role_id,
                        'description' => 'Permission ID ' . $old_pr->permission_id . ' removed from role ID ' . $old_pr->role_id,
                        'old_values' => json_encode($old_pr),
                        'new_values' => null,
                        'ip_address' => request()->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);

                    return response()->json([
                        'status' => 'success'
                    ]);
                }
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Permission role not found'
            ], 500);
        });
        
    }

    public function rolePermissions()
    {
        $permission_roles = PermissionRole::all();
        $groups = PermissionGroup::with('permissions')->get();
        $roles = Role::with('permissions')->get();
        return response()->json([
            'status' => 'success',
            'data' => [$permission_roles, $groups, $roles]
        ]);
    }

    public function userRolePermissions($id)
    {
        return response()->json(Role::find($id)->permissions);
    }

    public function userRoles($id)
    {
        return response()->json(User::find($id)->roles);
    }

    public function permissions()
    {
        return response()->json(Permission::all());
    }



}
