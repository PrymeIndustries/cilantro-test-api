<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'auditable_type', 'auditable_id',
        'description', 'old_values', 'new_values', 'ip_address',
        'academic_year_id'
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }
}
