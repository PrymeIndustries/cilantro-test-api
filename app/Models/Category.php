<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{

    protected $fillable = [
        'name', 'code', 'fees', 'report_sheet_template_id', 'class_master_teacher_id', 
        'has_subcategories', 'is_examination_class'
    ];


    protected $casts = [
        'cstp' => 'array'
    ];

    public function subcategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }


}
