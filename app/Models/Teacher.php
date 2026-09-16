<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Teacher extends Model
{

    protected $fillable = [
        'first_name', 'last_name', 'matricule', 'email', 'telephone',
        'gender', 'address', 'photo', 'academic_year_id'
    ];


    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teacher_code(): HasOne
    {
        return $this->hasOne(TeacherCode::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

}
