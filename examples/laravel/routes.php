<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SumsubController;

/**
 * Sumsub Integration Routes
 * Works with referral_code only, no authentication required
 *
 * Add these routes to your routes/web.php or routes/api.php
 */

// Web Routes (for displaying verification widget)
Route::middleware(['web'])->group(function () {

    // Show verification widget for specific referral code
    // Optional query params: ?email=user@example.com&phone=+1234567890
    Route::get('/sumsub/verify/{referralCode}', [SumsubController::class, 'show'])
        ->name('sumsub.verify');
});
