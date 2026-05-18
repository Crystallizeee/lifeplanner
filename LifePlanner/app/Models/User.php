<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// @see LP-DB-SCHEMA-2026-001 | Core Module — users
#[Fillable(['name', 'email', 'password', 'currency'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Relationships ──

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function savingsGoals(): HasMany
    {
        return $this->hasMany(SavingsGoal::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function todoLists(): HasMany
    {
        return $this->hasMany(TodoList::class);
    }

    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    public function weightLogs(): HasMany
    {
        return $this->hasMany(WeightLog::class);
    }

    public function mealPlanners(): HasMany
    {
        return $this->hasMany(MealPlanner::class);
    }

    public function groceryLists(): HasMany
    {
        return $this->hasMany(GroceryList::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    // ── Scopes ──

    public function activeBudget()
    {
        return $this->budgets()->where('is_active', true)->first();
    }

    public function seedDefaultCategories(): void
    {
        $categoryGroups = [
            'income' => [
                ['name' => 'Gaji', 'icon' => '💼'],
                ['name' => 'Freelance', 'icon' => '💻'],
                ['name' => 'Investasi', 'icon' => '📈'],
                ['name' => 'Lainnya', 'icon' => '💵'],
            ],
            'expense' => [
                ['name' => 'Makan & Minum', 'icon' => '🍽️'],
                ['name' => 'Transport', 'icon' => '🚗'],
                ['name' => 'Belanja', 'icon' => '🛍️'],
                ['name' => 'Hiburan', 'icon' => '🎬'],
                ['name' => 'Kesehatan', 'icon' => '🏥'],
                ['name' => 'Pendidikan', 'icon' => '📚'],
                ['name' => 'Lainnya', 'icon' => '📦'],
            ],
            'bill' => [
                ['name' => 'Listrik', 'icon' => '⚡'],
                ['name' => 'Internet', 'icon' => '🌐'],
                ['name' => 'Air', 'icon' => '💧'],
                ['name' => 'BPJS', 'icon' => '🏥'],
                ['name' => 'Sewa/Kos', 'icon' => '🏠'],
                ['name' => 'Cicilan', 'icon' => '🏦'],
            ],
            'saving' => [
                ['name' => 'Dana Darurat', 'icon' => '🛟'],
                ['name' => 'Investasi', 'icon' => '📊'],
                ['name' => 'Tabungan Umum', 'icon' => '🏦'],
            ],
            'task' => [
                ['name' => 'Pekerjaan', 'icon' => '💼'],
                ['name' => 'Personal', 'icon' => '👤'],
                ['name' => 'Belajar', 'icon' => '📖'],
                ['name' => 'Rumah Tangga', 'icon' => '🏠'],
            ],
            'grocery' => [
                ['name' => 'Sayur & Buah', 'icon' => '🥬'],
                ['name' => 'Daging & Ikan', 'icon' => '🥩'],
                ['name' => 'Dairy & Telur', 'icon' => '🥛'],
                ['name' => 'Bumbu & Rempah', 'icon' => '🧂'],
                ['name' => 'Snack & Minuman', 'icon' => '🍿'],
                ['name' => 'Kebutuhan Rumah', 'icon' => '🧹'],
            ],
            'investment' => [
                ['name' => 'Saham', 'icon' => '📊'],
                ['name' => 'Reksa Dana', 'icon' => '📈'],
                ['name' => 'Crypto', 'icon' => '₿'],
                ['name' => 'Emas', 'icon' => '🥇'],
                ['name' => 'Deposito', 'icon' => '🏦'],
                ['name' => 'Properti', 'icon' => '🏠'],
            ],
        ];

        foreach ($categoryGroups as $type => $categories) {
            foreach ($categories as $cat) {
                Category::firstOrCreate([
                    'user_id' => $this->id,
                    'name' => $cat['name'],
                    'type' => $type,
                ], [
                    'icon' => $cat['icon'],
                ]);
            }
        }
    }
}
