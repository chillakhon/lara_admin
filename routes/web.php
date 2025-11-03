<?php

use Illuminate\Support\Facades\Route;

Route::get('telegraph/{token}/webhook', function ($token) {
    return response()->json(['status' => 'ok'], 200);
});
