<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'username', 'email', 'password', 
        'system', 'photo'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'password',
    ];




    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    private function permissions(): array
    {
        $permissions = $this->role->permissions;
        /*$userPermissions = [];
        array_walk($roles, function ($role) use (&$userPermissions) {
            $userPermissions = array_merge($userPermissions, $role->permissions->pluck('slug'));
        });*/
        return array_unique($permissions);
    }

    public function hasRole($role): bool
    {
        return in_array($role, array_map(function ($r) use ($role) {
            return $r['slug'];
        }, $this->roles->toArray()));
    }

    public function hasAccess(array $permissions): bool
    {
        $userPermissions = $this->permissions();
        return (count(array_diff($userPermissions, $permissions)) + count($permissions) == count($userPermissions));
    }

    public function hasAtLeastAccess(array $permissions): bool
    {
        $userPermissions = $this->permissions();
        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions))
                return true;
        }
        return false;
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
