<?php

use Illuminate\Support\Facades\Route;

// Catch all routes and return the welcome view
// This allows React Router to handle client-side routing
Route::get('/{path?}', function () {
    return view('welcome');
})->where('path', '.*');
