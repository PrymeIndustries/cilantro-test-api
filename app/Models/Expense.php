<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'name', 'description', 'amount', 'academic_year_id'
    ];


    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

}
