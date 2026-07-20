<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $table = 'teachers';

    protected $fillable = [
        'tc_no',
        'password',
        'name',
        'surname',
        'email'
    ];
}