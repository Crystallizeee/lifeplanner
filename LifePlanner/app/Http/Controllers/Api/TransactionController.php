<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $transactions = Transaction::where('user_id', $userId)
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->limit, fn($q) => $q->limit($request->limit))
            ->paginate($request->get('per_page', 20));

        $data = $transactions->getCollection()->map(fn($t) => $this->formatTransaction($t));

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'total'        => $transactions->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'description'      => 'required|string|min:3|max:255',
            'amount'           => 'required|numeric|min:1',
            'type'             => 'required|in:income,expense,bill,saving',
            'category_id'      => 'required|exists:categories,id',
            'savings_goal_id'  => 'nullable|exists:savings_goals,id',
            'transaction_date' => 'required|date',
            'due_date'         => 'nullable|date',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $userId = $request->user()->id;

        $activeBudget = Budget::where('user_id', $userId)->where('is_active', true)->first();
        if (!$activeBudget) {
            $activeBudget = Budget::create([
                'user_id'          => $userId,
                'period_start'     => now()->startOfMonth()->format('Y-m-d'),
                'period_end'       => now()->endOfMonth()->format('Y-m-d'),
                'starting_balance' => 0,
                'is_active'        => true,
            ]);
        }

        $transaction = Transaction::create([
            'user_id'          => $userId,
            'budget_id'        => $activeBudget->id,
            'category_id'      => $request->category_id,
            'savings_goal_id'  => $request->type === 'saving' ? $request->savings_goal_id : null,
            'type'             => $request->type,
            'amount'           => $request->amount,
            'description'      => $request->description,
            'transaction_date' => $request->transaction_date,
            'due_date'         => $request->type === 'bill' ? $request->due_date : null,
            'status'           => $request->type === 'bill' ? 'planned' : 'paid',
            'notes'            => $request->notes,
        ]);

        if ($request->type === 'saving' && $request->savings_goal_id) {
            $goal = SavingsGoal::find($request->savings_goal_id);
            if ($goal) {
                $newSaved = $goal->current_saved + $request->amount;
                $goal->update([
                    'current_saved' => $newSaved,
                    'is_achieved'   => $newSaved >= $goal->target_amount,
                ]);
            }
        }

        $transaction->load('category');

        return response()->json([
            'success' => true,
            'data'    => $this->formatTransaction($transaction),
            'message' => 'Transaksi berhasil disimpan.',
        ], 201);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan.'], 404);
        }

        $transaction->load('category');

        return response()->json(['success' => true, 'data' => $this->formatTransaction($transaction)]);
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan.'], 404);
        }

        $request->validate([
            'description'      => 'sometimes|string|min:3|max:255',
            'amount'           => 'sometimes|numeric|min:1',
            'type'             => 'sometimes|in:income,expense,bill,saving',
            'category_id'      => 'sometimes|exists:categories,id',
            'transaction_date' => 'sometimes|date',
            'status'           => 'sometimes|in:paid,planned,overdue',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $transaction->update($request->only([
            'description', 'amount', 'type', 'category_id',
            'transaction_date', 'due_date', 'status', 'notes',
        ]));

        $transaction->load('category');

        return response()->json(['success' => true, 'data' => $this->formatTransaction($transaction)]);
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan.'], 404);
        }

        if ($transaction->type === 'saving' && $transaction->savings_goal_id) {
            $goal = SavingsGoal::find($transaction->savings_goal_id);
            if ($goal) {
                $newSaved = max(0, $goal->current_saved - $transaction->amount);
                $goal->update([
                    'current_saved' => $newSaved,
                    'is_achieved'   => $newSaved >= $goal->target_amount,
                ]);
            }
        }

        $transaction->delete();

        return response()->json(['success' => true, 'message' => 'Transaksi dihapus.']);
    }

    private function formatTransaction(Transaction $t): array
    {
        return [
            'id'               => $t->id,
            'description'      => $t->description,
            'amount'           => (float) $t->amount,
            'type'             => $t->type,
            'status'           => $t->status,
            'category_id'      => $t->category_id,
            'category_name'    => $t->category?->name ?? '-',
            'category_icon'    => $t->category?->icon ?? '📦',
            'transaction_date' => $t->transaction_date?->format('Y-m-d'),
            'due_date'         => $t->due_date?->format('Y-m-d'),
            'notes'            => $t->notes,
            'created_at'       => $t->created_at?->toIso8601String(),
        ];
    }
}
