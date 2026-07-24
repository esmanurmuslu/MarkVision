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
}