<?php

use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\IndustryController;
use App\Http\Controllers\Api\MatchingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OpportunitySuggestionController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\SavedOpportunityController;
use App\Http\Controllers\Api\ScrapingController;
use App\Http\Controllers\Api\StageController;
use App\Http\Controllers\Api\StartupController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UserSubscriptionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LinkedInAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::get('google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
    Route::get('linkedin', [LinkedInAuthController::class, 'redirect'])->name('auth.linkedin.redirect');
    Route::get('linkedin/callback', [LinkedInAuthController::class, 'callback'])->name('auth.linkedin.callback');

    Route::middleware('auth:api')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->get('/user', function (\Illuminate\Http\Request $request) {
    return $request->user();
});

Route::middleware('auth:api')->prefix('opportunities')->group(function (): void {
    Route::get('/', [OpportunityController::class, 'index']);
    Route::get('/{opportunity}', [OpportunityController::class, 'show']);
    Route::post('/', [OpportunityController::class, 'store'])->middleware('can:manage_opportunities');
    Route::put('/{opportunity}', [OpportunityController::class, 'update'])->middleware('can:manage_opportunities');
    Route::delete('/{opportunity}', [OpportunityController::class, 'destroy'])->middleware('can:manage_opportunities');
});

Route::middleware('auth:api')->prefix('startups')->group(function (): void {
    Route::get('/', [StartupController::class, 'index']);
    Route::post('/', [StartupController::class, 'store']);
    Route::get('/{startup}', [StartupController::class, 'show']);
    Route::put('/{startup}', [StartupController::class, 'update']);
    Route::delete('/{startup}', [StartupController::class, 'destroy']);
});

Route::middleware('auth:api')->prefix('subscriptions')->group(function (): void {
    Route::get('/', [SubscriptionController::class, 'index']);
    Route::get('/my', [SubscriptionController::class, 'my']);
});

Route::middleware('auth:api')->prefix('user-subscriptions')->group(function (): void {
    Route::post('/subscribe', [UserSubscriptionController::class, 'subscribe']);
    Route::post('/cancel', [UserSubscriptionController::class, 'cancel']);
});

Route::middleware('auth:api')->prefix('startups/{startup}')->group(function (): void {
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
    Route::get('/matches', [MatchingController::class, 'index']);
    Route::get('/saved-opportunities', [SavedOpportunityController::class, 'index']);
    Route::post('/opportunities/{opportunity}/save', [SavedOpportunityController::class, 'save']);
    Route::delete('/opportunities/{opportunity}/save', [SavedOpportunityController::class, 'unsave']);
});

Route::middleware('auth:api')->post('/scraping/run', [ScrapingController::class, 'run']);

Route::middleware('auth:api')->prefix('notifications')->group(function (): void {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});

Route::middleware('optional_auth')->post('/opportunity-suggestions', [OpportunitySuggestionController::class, 'store']);

Route::get('/industries', [IndustryController::class, 'index']);
Route::get('/stages', [StageController::class, 'index']);
