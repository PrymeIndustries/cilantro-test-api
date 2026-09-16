<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guardian extends Model
{
    protected $fillable = [
        'name', 'email', 'telephone', 'telephone_2',
        'gender', 'address', 'occupation', 'photo', 'academic_year_id'
    ];


    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student_code(): HasOne
    {
        return $this->hasOne(StudentCode::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

}
