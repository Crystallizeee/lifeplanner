<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $investments = Investment::where('user_id', $request->user()->id)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(fn($i) => $this->format($i));
            
        return response()->json(['success' => true, 'data' => $investments]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category_id'      => 'nullable|exists:categories,id',
            'name'             => 'required|string|max:255',
            'symbol'           => 'nullable|string|max:50',
            'total_units'      => 'required|numeric|min:0',
            'average_buy_price'=> 'required|numeric|min:0',
            'current_price'    => 'required|numeric|min:0',
            'platform'         => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $investment = Investment::create([
            'user_id'          => $request->user()->id,
            'category_id'      => $request->category_id,
            'name'             => $request->name,
            'symbol'           => $request->symbol,
            'total_units'      => $request->total_units,
            'average_buy_price'=> $request->average_buy_price,
            'current_price'    => $request->current_price,
            'platform'         => $request->platform,
            'notes'            => $request->notes,
        ]);
        
        $investment->load('category');
        return response()->json(['success' => true, 'data' => $this->format($investment)], 201);
    }

    public function show(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $investment->load('category');
        return response()->json(['success' => true, 'data' => $this->format($investment)]);
    }

    public function update(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) return response()->json(['success' => false], 404);

        $request->validate([
            'category_id'      => 'nullable|exists:categories,id',
            'name'             => 'sometimes|string|max:255',
            'symbol'           => 'nullable|string|max:50',
            'total_units'      => 'sometimes|numeric|min:0',
            'average_buy_price'=> 'sometimes|numeric|min:0',
            'current_price'    => 'sometimes|numeric|min:0',
            'platform'         => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $investment->update($request->only([
            'category_id', 'name', 'symbol', 'total_units', 
            'average_buy_price', 'current_price', 'platform', 'notes'
        ]));
        
        $investment->load('category');
        return response()->json(['success' => true, 'data' => $this->format($investment->fresh())]);
    }

    public function destroy(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $investment->delete();
        return response()->json(['success' => true]);
    }

    private function format(Investment $i): array
    {
        $totalInvested = $i->total_units * $i->average_buy_price;
        $currentValue = $i->total_units * $i->current_price;
        $pnl = $currentValue - $totalInvested;
        $pnlPct = $totalInvested > 0 ? ($pnl / $totalInvested) * 100 : 0;
        
        return [
            'id'                => $i->id,
            'name'              => $i->name,
            'symbol'            => $i->symbol,
            'category_name'     => $i->category?->name ?? 'Lainnya',
            'total_units'       => (float) $i->total_units,
            'average_buy_price' => (float) $i->average_buy_price,
            'current_price'     => (float) $i->current_price,
            'total_invested'    => (float) $totalInvested,
            'current_value'     => (float) $currentValue,
            'pnl'               => (float) $pnl,
            'pnl_pct'           => (float) $pnlPct,
            'platform'          => $i->platform,
            'notes'             => $i->notes,
        ];
    }
}
