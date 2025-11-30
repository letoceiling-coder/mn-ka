<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminMenuController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\DecisionBlockSettingsController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizBlockSettingsController;
use App\Http\Controllers\Api\QuizSubmissionController;
use App\Http\Controllers\Api\DeployController;
use App\Http\Controllers\Api\v1\FolderController;
use App\Http\Controllers\Api\v1\MediaController;
use Illuminate\Support\Facades\Route;

// Публичные роуты
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Защищённые роуты
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    // Меню
    Route::get('/admin/menu', [AdminMenuController::class, 'index']);
    
    // Уведомления
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/all', [NotificationController::class, 'all']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    
    // Media API (v1)
    Route::prefix('v1')->group(function () {
        // Folders
        Route::get('folders/tree/all', [FolderController::class, 'tree'])->name('folders.tree');
        Route::post('folders/update-positions', [FolderController::class, 'updatePositions'])->name('folders.update-positions');
        Route::post('folders/{id}/restore', [FolderController::class, 'restore'])->name('folders.restore');
        Route::apiResource('folders', FolderController::class);
        
        // Media
        Route::post('media/{id}/restore', [MediaController::class, 'restore'])->name('media.restore');
        Route::delete('media/trash/empty', [MediaController::class, 'emptyTrash'])->name('media.trash.empty');
        Route::apiResource('media', MediaController::class);
        
        // Admin only routes (Roles and Users management)
        Route::middleware('admin')->group(function () {
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('users', UserController::class);
            Route::post('menus/update-order', [MenuController::class, 'updateOrder']);
            Route::apiResource('menus', MenuController::class);
            Route::apiResource('banners', BannerController::class);
            
            // Decision Block Management
            Route::post('chapters/update-order', [ChapterController::class, 'updateOrder']);
            Route::apiResource('chapters', ChapterController::class);
            Route::get('products/export', [ProductController::class, 'export']);
            Route::post('products/import', [ProductController::class, 'import']);
            Route::apiResource('products', ProductController::class);
            Route::get('services/export', [ServiceController::class, 'export']);
            Route::post('services/import', [ServiceController::class, 'import']);
            Route::apiResource('services', ServiceController::class);
            Route::get('decision-block-settings', [DecisionBlockSettingsController::class, 'show']);
            Route::put('decision-block-settings', [DecisionBlockSettingsController::class, 'update']);
            Route::apiResource('quizzes', QuizController::class);
            Route::get('quiz-block-settings', [QuizBlockSettingsController::class, 'show']);
            Route::put('quiz-block-settings', [QuizBlockSettingsController::class, 'update']);
        });
    });
});

// Публичные маршруты для меню (без авторизации)
Route::get('/public/menus/{type}', [MenuController::class, 'getPublicMenu'])->where('type', 'header|footer|burger');

// Публичные маршруты для баннеров (без авторизации)
Route::get('/public/banners/{slug}', [BannerController::class, 'getBySlug']);

// Публичные маршруты для Decision Block (без авторизации)
Route::get('/public/decision-block/chapters', [ChapterController::class, 'index']);
Route::get('/public/decision-block/settings', [DecisionBlockSettingsController::class, 'show']);

// Публичные маршруты для Quiz Block (без авторизации)
Route::get('/public/quiz-block/settings', [QuizBlockSettingsController::class, 'show']);
Route::get('/public/quiz-block/quiz/{id}', [QuizController::class, 'showPublic']);
Route::post('/public/quiz/submit', [QuizSubmissionController::class, 'submit']);

// Маршрут для деплоя (защищен токеном)
Route::post('/deploy', [DeployController::class, 'deploy'])
    ->middleware('deploy.token');


