<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app');

Route::get('/{any}', fn () => view('app'))->where('any', '^(?!api\/).*$');
