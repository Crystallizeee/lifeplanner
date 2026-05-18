<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard;
use App\Livewire\Finance\BillTracker;
use App\Livewire\Finance\BudgetOverview;
use App\Livewire\Finance\InvestmentTracker;
use App\Livewire\Finance\QuickEntry;
use App\Livewire\Finance\SavingsGoals;
use App\Livewire\Health\GroceryListPage;
use App\Livewire\Health\HabitMatrix;
use App\Livewire\Health\MealPlannerPage;
use App\Livewire\Health\WeightLogPage;
use App\Livewire\Productivity\GoalTracker;
use App\Livewire\Productivity\KanbanTodo;
use App\Livewire\Settings\SettingsManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Guest Routes ──
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

// ── Logout ──
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');

// ── Authenticated Routes ──
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', Dashboard::class)->name('dashboard');

    // ── Finance Module ──
    Route::get('/finance/quick-entry', QuickEntry::class)->name('finance.quick-entry');
    Route::get('/finance/budget', BudgetOverview::class)->name('finance.budget');
    Route::get('/finance/bills', BillTracker::class)->name('finance.bills');
    Route::get('/finance/savings', SavingsGoals::class)->name('finance.savings');
    Route::get('/finance/investments', InvestmentTracker::class)->name('finance.investments');

    // ── Productivity Module ──
    Route::get('/productivity/todos', KanbanTodo::class)->name('productivity.todos');
    Route::get('/productivity/goals', GoalTracker::class)->name('productivity.goals');

    // ── Health Module ──
    Route::get('/health/habits', HabitMatrix::class)->name('health.habits');
    Route::get('/health/weight', WeightLogPage::class)->name('health.weight');
    Route::get('/health/meals', MealPlannerPage::class)->name('health.meals');
    Route::get('/health/grocery', GroceryListPage::class)->name('health.grocery');

    // ── Settings Module ──
    Route::get('/settings', SettingsManager::class)->name('settings');
});
