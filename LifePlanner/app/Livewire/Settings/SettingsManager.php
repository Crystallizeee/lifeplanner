<?php

namespace App\Livewire\Settings;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TodoList;
use App\Models\WeightLog;
use App\Models\GroceryList;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pengaturan & Utilitas — LifePlanner SIM')]
class SettingsManager extends Component
{
    public string $activeTab = 'categories'; // categories, export

    // Category Creation
    public bool $showCategoryForm = false;
    public ?int $editingCategoryId = null;

    #[Validate('required|string|min:2|max:50')]
    public string $categoryName = '';

    #[Validate('required|in:income,expense,bill,saving,task,grocery')]
    public string $categoryType = 'expense';

    #[Validate('required|string|max:5')]
    public string $categoryIcon = '📦';

    public string $filterType = 'expense';

    public function selectTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function saveCategory(): void
    {
        $this->validate();

        $userId = Auth::id();

        if ($this->editingCategoryId) {
            // Update
            Category::where('user_id', $userId)
                ->where('id', $this->editingCategoryId)
                ->update([
                    'name' => $this->categoryName,
                    'type' => $this->categoryType,
                    'icon' => $this->categoryIcon,
                ]);
            $this->dispatch('toast', message: 'Kategori berhasil diperbarui! 📝', type: 'success');
        } else {
            // Create
            Category::create([
                'user_id' => $userId,
                'name' => $this->categoryName,
                'type' => $this->categoryType,
                'icon' => $this->categoryIcon,
            ]);
            $this->dispatch('toast', message: 'Kategori baru ditambahkan! 🌟', type: 'success');
        }

        $this->resetCategoryForm();
    }

    public function startEditCategory(int $id): void
    {
        $category = Category::where('user_id', Auth::id())->find($id);
        if ($category) {
            $this->editingCategoryId = $id;
            $this->categoryName = $category->name;
            $this->categoryType = $category->type;
            $this->categoryIcon = $category->icon;
            $this->showCategoryForm = true;
        }
    }

    public function deleteCategory(int $id): void
    {
        $category = Category::where('user_id', Auth::id())->find($id);
        if (!$category) return;

        // Relation checks
        $transactionCount = Transaction::where('category_id', $id)->count();
        $taskCount = TodoList::where('category_id', $id)->count();
        $groceryCount = GroceryList::where('category_id', $id)->count();

        if ($transactionCount > 0 || $taskCount > 0 || $groceryCount > 0) {
            $this->dispatch('toast', message: 'Gagal: Kategori masih digunakan oleh data lain!', type: 'error');
            return;
        }

        $category->delete();
        $this->dispatch('toast', message: 'Kategori telah dihapus.', type: 'warning');
    }

    public function resetCategoryForm(): void
    {
        $this->reset(['categoryName', 'categoryType', 'categoryIcon', 'editingCategoryId', 'showCategoryForm']);
        $this->categoryIcon = '📦';
        $this->categoryType = 'expense';
    }

    // ── Export Utilities ──

    public function exportTransactions()
    {
        $userId = Auth::id();
        $transactions = Transaction::where('user_id', $userId)
            ->with('category')
            ->orderByDesc('transaction_date')
            ->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=lifeplanner_transactions_' . now()->format('Y-m-d') . '.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['ID', 'Keterangan', 'Jumlah', 'Tipe', 'Kategori', 'Tanggal Transaksi', 'Jatuh Tempo (Tagihan)', 'Status', 'Catatan']);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->id,
                    $tx->description,
                    $tx->amount,
                    $tx->type,
                    $tx->category->name ?? '-',
                    $tx->transaction_date->format('Y-m-d'),
                    $tx->due_date?->format('Y-m-d') ?? '-',
                    $tx->status ?? '-',
                    $tx->notes ?? ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportWeightLogs()
    {
        $userId = Auth::id();
        $logs = WeightLog::where('user_id', $userId)->orderByDesc('logged_at')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=lifeplanner_weight_logs_' . now()->format('Y-m-d') . '.csv',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['ID', 'Berat Badan (kg)', 'Body Fat (%)', 'Tanggal Log', 'Catatan']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->weight,
                    $log->body_fat ?? '-',
                    $log->logged_at->format('Y-m-d'),
                    $log->notes ?? ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportTodos()
    {
        $userId = Auth::id();
        $tasks = TodoList::where('user_id', $userId)->with('category')->orderBy('status')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=lifeplanner_todos_' . now()->format('Y-m-d') . '.csv',
        ];

        $callback = function() use ($tasks) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['ID', 'Nama Task', 'Status', 'Prioritas', 'Kategori', 'Jatuh Tempo', 'Catatan']);

            foreach ($tasks as $task) {
                fputcsv($file, [
                    $task->id,
                    $task->task_name,
                    $task->status,
                    $task->priority,
                    $task->category->name ?? '-',
                    $task->due_date?->format('Y-m-d') ?? '-',
                    $task->notes ?? ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $userId = Auth::id();

        $categories = Category::where('user_id', $userId)
            ->where('type', $this->filterType)
            ->orderBy('name')
            ->get();

        return view('livewire.settings.settings-manager', [
            'categories' => $categories,
        ]);
    }
}
