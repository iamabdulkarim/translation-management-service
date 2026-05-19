<?php

use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Http\Controllers\Api\V1\AuthenticatedUserController;
use App\Http\Controllers\Api\V1\AuthenticationController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TranslationController;
use App\Http\Controllers\Api\V1\TranslationExportController;
use App\Http\Controllers\Api\V1\TranslationSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('health', HealthController::class)->name('api.v1.health');
    Route::post('auth/login', [AuthenticationController::class, 'store'])->name('api.v1.auth.login');

    Route::middleware('auth.token')->group(function (): void {
        Route::get('me', AuthenticatedUserController::class)->name('api.v1.me');
        Route::post('auth/logout', [AuthenticationController::class, 'destroy'])->name('api.v1.auth.logout');
    });

    Route::get('auth/tokens', [ApiTokenController::class, 'index'])
        ->middleware('auth.token:tokens:read')
        ->name('api.v1.tokens.index');
    Route::delete('auth/tokens/{apiToken}', [ApiTokenController::class, 'destroy'])
        ->middleware('auth.token:tokens:write')
        ->name('api.v1.tokens.destroy');

    Route::get('translations/search', TranslationSearchController::class)
        ->middleware('auth.token:translations:read')
        ->name('api.v1.translations.search');
    Route::get('translations/export/{locale}', TranslationExportController::class)
        ->middleware('auth.token:translations:export')
        ->where('locale', '[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})?')
        ->name('api.v1.translations.export');
    Route::get('translations', [TranslationController::class, 'index'])
        ->middleware('auth.token:translations:read')
        ->name('api.v1.translations.index');
    Route::post('translations', [TranslationController::class, 'store'])
        ->middleware('auth.token:translations:write')
        ->name('api.v1.translations.store');
    Route::get('translations/{translation}', [TranslationController::class, 'show'])
        ->middleware('auth.token:translations:read')
        ->name('api.v1.translations.show');
    Route::match(['put', 'patch'], 'translations/{translation}', [TranslationController::class, 'update'])
        ->middleware('auth.token:translations:write')
        ->name('api.v1.translations.update');
    Route::delete('translations/{translation}', [TranslationController::class, 'destroy'])
        ->middleware('auth.token:translations:write')
        ->name('api.v1.translations.destroy');
});
