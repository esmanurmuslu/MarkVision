use App\Http\Controllers\MarkVisionController;
Route::post('/optik-oku', [MarkVisionController::class, 'formuOkuAPI']);
Route::post('/register', [App\Http\Controllers\MarkVisionController::class, 'register']);