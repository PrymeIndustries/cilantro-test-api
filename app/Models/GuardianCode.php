<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianCode extends Model
{
    protected $fillable = [ 'code', 'guardian_id' ];

    protected $casts = [
        'code' => 'encrypted'
    ];


    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

}
