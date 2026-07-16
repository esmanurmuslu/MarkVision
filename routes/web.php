<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use App\Jobs\ProcessExamImageJob;

Route::get('/test-gonder', function () {
    $path = base_path('omr_scripts/optik.png');
    ProcessExamImageJob::dispatch(1, $path);
    return "İş kuyruğa atıldı! queue:work terminalini kontrol et.";
});
