<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionType extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'weight_percentage', 'category_id'
    ];


    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function student_marks(): HasMany
    {
        return $this->hasMany(StudentMark::class);
    }

}
