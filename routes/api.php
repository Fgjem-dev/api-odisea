<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PersonaController;


Route::middleware(['api_key'])->group(function () {
    Route::get('/persona/{id}/registro', [PersonaController::class, 'show']);
    Route::get('/persona/{id}/imagen', [PersonaController::class, 'imagenGrande']);
    Route::get('/persona/{id}/imagen-thumb', [PersonaController::class, 'imagenMiniatura']);

});

 Route::get('/persona/{id}/imagen', [PersonaController::class, 'imagenGrande']);
 Route::get('/persona/{id}/imagen-thumb', [PersonaController::class, 'imagenMiniatura']);
