<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'departments';

    public $timestamps = false;

    protected $fillable = [
        'faculty_id',
        'department_name',
        'degree_type'
    ];

    // Bölüm bir fakülteye aittir
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    // Bir bölümün birden fazla dersi olabilir
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'department_id');
    }
}