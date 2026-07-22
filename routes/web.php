<?php

use Illuminate\Support\Facades\Route;

// Geçici test route'u
Route::get('/', function () {
    return 'OBS sistemi çalışıyor';
});

// Login sayfası (AuthController olmadığı için şimdilik test)
Route::get('/obs/login', function () {
    return 'Login sayfası';
});