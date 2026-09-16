<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationHistory extends Model
{
    protected $fillable = [
        'to', 'message', 'academic_year_id'
    ];

    protected $casts = [
      'to' => 'encrypted:array',
      'message' => 'encrypted'
    ];


    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

}
