<?php

use App\Http\Controllers\EscanerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);

Route::post('/escaneos', [EscanerController::class, 'escanear']);
