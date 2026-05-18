<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN for enums/check constraints.
            // We must recreate the table with the new enum values.
            // Disable FK checks to allow dropping referenced table
            DB::statement('PRAGMA foreign_keys = OFF');

            // Step 1: Create temp table with new schema
            DB::statement("
                CREATE TABLE categories_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    name VARCHAR(80) NOT NULL,
                    type VARCHAR(255) NOT NULL CHECK (type IN ('income', 'expense', 'bill', 'saving', 'task', 'grocery', 'investment')),
                    icon VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            // Step 2: Copy existing data
            DB::statement('INSERT INTO categories_new (id, user_id, name, type, icon, created_at) SELECT id, user_id, name, type, icon, created_at FROM categories');

            // Step 3: Drop old table
            DB::statement('DROP TABLE categories');

            // Step 4: Rename new table
            DB::statement('ALTER TABLE categories_new RENAME TO categories');

            // Re-enable FK checks
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('income', 'expense', 'bill', 'saving', 'task', 'grocery', 'investment') NOT NULL");
            } else {
                // PostgreSQL or others
                DB::statement("ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_type_check");
                DB::statement("ALTER TABLE categories ALTER COLUMN type TYPE VARCHAR(255)");
                DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_type_check CHECK (type IN ('income', 'expense', 'bill', 'saving', 'task', 'grocery', 'investment'))");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement("
                CREATE TABLE categories_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    name VARCHAR(80) NOT NULL,
                    type VARCHAR(255) NOT NULL CHECK (type IN ('income', 'expense', 'bill', 'saving', 'task', 'grocery')),
                    icon VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            DB::statement("INSERT INTO categories_old (id, user_id, name, type, icon, created_at) SELECT id, user_id, name, type, icon, created_at FROM categories WHERE type != 'investment'");

            DB::statement('DROP TABLE categories');
            DB::statement('ALTER TABLE categories_old RENAME TO categories');

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            if (DB::getDriverName() === 'mysql') {
                DB::table('categories')->where('type', 'investment')->update(['type' => 'expense']);
                DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('income', 'expense', 'bill', 'saving', 'task', 'grocery') NOT NULL");
            } else {
                DB::table('categories')->where('type', 'investment')->update(['type' => 'expense']);
                DB::statement("ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_type_check");
                DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_type_check CHECK (type IN ('income', 'expense', 'bill', 'saving', 'task', 'grocery'))");
            }
        }
    }
};
