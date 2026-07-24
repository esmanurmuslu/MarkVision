<?php

namespace App\Models\Obs;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $table = 'teachers';

    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'tc_no'
    ];
}