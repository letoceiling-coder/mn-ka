<?php

use Illuminate\Support\Facades\Route;

// Единая точка входа для SPA (все маршруты обрабатываются Vue Router на клиенте)
// Исключаем API маршруты, чтобы они обрабатывались routes/api.php
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
