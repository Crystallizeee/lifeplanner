<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ChartController extends Controller
{
    /**
     * Monthly Cashflow Chart — returns income & expense totals grouped by month.
     * Query params: months (default 6)
     */
    public function cashflow(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $months = (int) $request->input('months', 6);
        $months = min(max($months, 1), 24); // clamp 1–24

        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $rows = Transaction::where('user_id', $userId)
            ->where('transaction_date', '>=', $startDate)
            ->selectRaw("
                TO_CHAR(transaction_date, 'YYYY-MM') AS month,
                type,
                SUM(amount) AS total
            ")
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get();

        // Build month map
        $data = [];
        for ($i = 0; $i < $months; $i++) {
            $m = Carbon::now()->subMonths($months - 1 - $i)->format('Y-m');
            $data[$m] = [
                'month'   => $m,
                'label'   => Carbon::parse($m . '-01')->format('M Y'),
                'income'  => 0.0,
                'expense' => 0.0,
            ];
        }

        foreach ($rows as $row) {
            $key = $row->month;
            if (isset($data[$key])) {
                if ($row->type === 'income') {
                    $data[$key]['income'] = (float) $row->total;
                } elseif ($row->type === 'expense') {
                    $data[$key]['expense'] = (float) $row->total;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => array_values($data),
        ]);
    }

    /**
     * Spending breakdown by category for the active budget.
     */
    public function spendingBreakdown(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $breakdown = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->orderByDesc('total')
            ->take(8)
            ->get()
            ->map(fn($t) => [
                'name'  => $t->category->name ?? 'Lainnya',
                'icon'  => $t->category->icon ?? '📦',
                'total' => (float) $t->total,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $breakdown,
        ]);
    }
}
