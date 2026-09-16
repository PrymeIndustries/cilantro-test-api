<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCategory extends Model
{
    protected $fillable = [
      'name', 'code', 'category_id', 'class_master_id', 'results'
    ];

    protected $casts = [
        'results' => 'array'
    ];


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

}
