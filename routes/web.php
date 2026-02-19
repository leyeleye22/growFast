<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Redirection /docs → /api-docs (spec JSON) pour compatibilité
Route::redirect('/docs', '/api-docs', 301);
