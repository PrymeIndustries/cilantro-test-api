<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description',
        'system', 'head'
    ];

    protected $casts = [
        'system' => 'boolean',
        'head' => 'boolean'
    ];


    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function user (): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users (): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeSystem (Builder $builder, $system = true)
    {
        $builder->where('system', true);
    }

    /**
     * Verify if role has all provided permissions seperated by pipes
     * @param string $permissions
     * @return bool
     * @example $role->hasPermissions('e|r|u|d')
     */
    public function hasPermissions (string $permissions): bool
    {
        $permissions = explode('|', $permissions); //e.g., user.create|user.manage|user.update
        $user_permissions = $this->permissions->pluck('slug');
        $valid = true;

        foreach ($permissions as $permission) {
            if (!in_array($permission, $user_permissions)) {
                $valid = false;
                break;
            }
        }
        return $valid;
    }

    /**
     * Verify if role has any of the provided permissions seperated by pipes
     * @param $permissions string
     * @return bool
     * @example $role->hasAnyPermission('e|r|u|d')
     */
    public function hasAnyPermission (string $permissions): bool
    {
        $permissions = explode('|', $permissions); //e.g., user.create|user.manage|user.update
        $user_permissions = $this->permissions->pluck('slug');
        $valid = false;

        foreach ($permissions as $permission) {
            if (in_array($permission, $user_permissions)) {
                $valid = true;
                break;
            }
        }
        return $valid;
    }

}
