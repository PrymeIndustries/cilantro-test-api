<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'full_name', 'date_of_birth', 'matricule', 'email', 'telephone', 'gender', 
        'address', 'category_id', 'sub_category_id', 'guardian_id', 'guardian_name', 
        'fees', 'fees_amount_paid', 'fees_status', 'fees_history', 'paid_fees', 
        'photo', 'academic_year_id'
    ];

    protected $casts = [
      'fees_history' => 'array'
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sub_category(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function result(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function report_sheet(): HasMany
    {
        return $this->hasMany(ReportSheet::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(StudentMark::class);
    }

    public function descipline_records(): HasMany
    {
        return $this->hasMany(DisciplineRecord::class);
    }

}
