<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Laravel'e varsayılan 'users' yerine senin 'teachers' tablonu kullanmasını söylüyoruz
    protected $table = 'teachers'; 

    // Tablondaki sütun isimlerine göre buraları doldurabilirsin (Örn: email, password vb.)
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}