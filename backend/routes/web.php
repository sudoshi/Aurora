<?php

use Illuminate\Support\Facades\Route;

// Catch all routes and return the SPA shell
// React Router handles client-side routing
Route::get('/{path?}', function () {
    return view('app');
})->where('path', '^(?!api).*$');
