<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    protected $table = 'faculties';

    public $timestamps = false;

    protected $fillable = [
        'faculty_name'
    ];

    // Bir fakültenin birden fazla bölümü olabilir
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'faculty_id');
    }
}