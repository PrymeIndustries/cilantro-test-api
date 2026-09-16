<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = [
        'year', 'status', 'active', 'temp_active'
    ];


    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function student_marks(): HasMany
    {
        return $this->hasMany(StudentMark::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function report_sheets(): HasMany
    {
        return $this->hasMany(ReportSheet::class);
    }

    public function discipline_records(): HasMany
    {
        return $this->hasMany(DisciplineRecord::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function income_records(): HasMany
    {
        return $this->hasMany(IncomeRecord::class);
    }

    public function inboxes(): HasMany
    {
        return $this->hasMany(Inbox::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function communication_history(): HasMany
    {
        return $this->hasMany(CommunicationHistory::class);
    }

}
