<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Course extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'department_id',
        'course_code',
        'course_name'
    ];

    // Ders bir bölüme aittir.
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}