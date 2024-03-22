<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FetchWebsiteController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', [FetchWebsiteController::class, 'fetch']);
