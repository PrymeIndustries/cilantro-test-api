<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inbox extends Model
{
    protected $fillable = [
      'from_', 'message', 'seen', 'academic_year_id'
    ];

    protected $casts = [
      'from_' => 'encrypted:array',
      'message' => 'encrypted'
    ];


    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

}
