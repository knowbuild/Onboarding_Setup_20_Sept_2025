<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome'); // Ensure 'welcome.blade.php' exists in resources/views
});
