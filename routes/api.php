<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['request.id', 'sec.headers', 'throttle:api'])
    ->group(base_path('routes/api_v1.php'));
