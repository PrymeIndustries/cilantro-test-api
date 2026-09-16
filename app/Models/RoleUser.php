<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    protected $table = "role_user";
	protected $fillable = [];

	public function role()
	{
		return $this->belongsTo(Role::class);
	}

	public function user ()
	{
		return $this->belongsTo(User::class);
	}
}
