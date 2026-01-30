<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Redirect to admin panel (shows login if not authenticated, dashboard if authenticated)
    return redirect('/admin');
});
