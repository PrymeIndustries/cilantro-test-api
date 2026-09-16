<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timetable extends Model
{
    protected $fillable = [
        'title', 'params', 'value', 'category_id', 'sub_category_id',
        'is_general', 'status', 'term_id'
    ];


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function subjectPeriods(): HasMany
    {
        return $this->hasMany(SubjectPeriod::class);
    }

}
