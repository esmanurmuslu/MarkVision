<?php

namespace App\Models\Obs;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';

    public $timestamps = false;

    protected $fillable = [
        'faculty_id',
        'department_name',
        'degree_type'
    ];

    public function faculty()
    {
        return $this->belongsTo(
            Faculty::class,
            'faculty_id'
        );
    }
}