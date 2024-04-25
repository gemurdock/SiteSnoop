<?php

use Illuminate\Support\Facades\Route;
use App\Services\WebFetcher;

Route::get('/', function () {
    return view('welcome');
});
