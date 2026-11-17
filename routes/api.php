<?php

use App\Http\Controllers\EscanerController;
use Illuminate\Support\Facades\Route;

Route::post('/escaneos', [EscanerController::class, 'escanear']);
