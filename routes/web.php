<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Redirection /docs → /api-docs (spec JSON) pour compatibilité
Route::redirect('/docs', '/api-docs', 301);

// Redirection ancienne URL Swagger (évite 403 Hostinger sur "documentation")
Route::redirect('/api/documentation', '/api/swagger-ui', 301);
