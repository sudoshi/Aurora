<?php

use Illuminate\Support\Facades\Route;

// Catch all routes and return the SPA shell
// React Router handles client-side routing
Route::get('/{path?}', function () {
    return response(view('app'))
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->where('path', '^(?!api).*$');
