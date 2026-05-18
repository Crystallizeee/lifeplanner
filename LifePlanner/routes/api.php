<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\HabitController;
use App\Http\Controllers\Api\WeightLogController;
use App\Http\Controllers\Api\InvestmentController;
use Illuminate\Support\Facades\Route;

// ── Public: Auth ──
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// ── Protected: Sanctum Token Auth ──
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Finance
    Route::apiResource('/transactions', TransactionController::class);
    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/budgets', BudgetController::class);
    Route::apiResource('/savings-goals', SavingsGoalController::class);
    Route::apiResource('/investments', InvestmentController::class);

    // Productivity
    Route::apiResource('/goals', GoalController::class);
    Route::apiResource('/todos', TodoController::class);
    Route::post('/todos/{todo}/toggle', [TodoController::class, 'toggle']);

    // Health
    Route::apiResource('/habits', HabitController::class);
    Route::post('/habits/{habit}/log', [HabitController::class, 'logToday']);
    Route::apiResource('/weight-logs', WeightLogController::class);
});
