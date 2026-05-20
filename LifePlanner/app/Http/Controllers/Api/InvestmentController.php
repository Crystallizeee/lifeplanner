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
            ->orderBy('is_sold')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($i) => $this->format($i));
            
        return response()->json(['success' => true, 'data' => $investments]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'asset_name'    => 'required|string|max:150',
            'asset_type'    => 'required|in:saham,reksadana,crypto,emas,deposito,properti,lainnya',
            'quantity'      => 'required|numeric|min:0.00000001',
            'buy_price'     => 'required|numeric|min:0',
            'current_price' => 'required|numeric|min:0',
            'buy_date'      => 'required|date',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $investment = Investment::create([
            'user_id'       => $request->user()->id,
            'asset_name'    => $request->asset_name,
            'asset_type'    => $request->asset_type,
            'quantity'      => $request->quantity,
            'buy_price'     => $request->buy_price,
            'current_price' => $request->current_price,
            'buy_date'      => $request->buy_date,
            'notes'         => $request->notes,
        ]);
        
        // Log the initial buy
        \App\Models\InvestmentLog::create([
            'investment_id' => $investment->id,
            'user_id'       => $request->user()->id,
            'action'        => 'buy',
            'quantity'      => $request->quantity,
            'price'         => $request->buy_price,
            'notes'         => 'Pembelian awal (API)',
            'logged_at'     => $request->buy_date . ' ' . now()->format('H:i:s'),
        ]);
        
        return response()->json(['success' => true, 'data' => $this->format($investment)], 201);
    }

    public function show(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        return response()->json(['success' => true, 'data' => $this->format($investment)]);
    }

    public function update(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) return response()->json(['success' => false], 404);

        $request->validate([
            'asset_name'    => 'sometimes|string|max:150',
            'asset_type'    => 'sometimes|in:saham,reksadana,crypto,emas,deposito,properti,lainnya',
            'quantity'      => 'sometimes|numeric|min:0.00000001',
            'buy_price'     => 'sometimes|numeric|min:0',
            'current_price' => 'sometimes|numeric|min:0',
            'buy_date'      => 'sometimes|date',
            'notes'         => 'nullable|string|max:1000',
            'is_sold'       => 'sometimes|boolean',
            'sold_price'    => 'nullable|numeric|min:0',
            'sold_date'     => 'nullable|date',
        ]);

        $oldPrice = $investment->current_price;
        $wasAlreadySold = $investment->is_sold;

        $investment->update($request->only([
            'asset_name', 'asset_type', 'quantity', 'buy_price', 
            'current_price', 'buy_date', 'notes', 'is_sold', 'sold_price', 'sold_date'
        ]));
        
        $fresh = $investment->fresh();

        // Log price updates
        if ($request->has('current_price') && $request->current_price != $oldPrice) {
            \App\Models\InvestmentLog::create([
                'investment_id' => $fresh->id,
                'user_id'       => $request->user()->id,
                'action'        => 'price_update',
                'price'         => $request->current_price,
                'notes'         => 'Update harga (API): Rp ' . number_format($oldPrice, 0, ',', '.') . ' → Rp ' . number_format($request->current_price, 0, ',', '.'),
                'logged_at'     => now(),
            ]);
        }

        // Log sale completion
        if ($fresh->is_sold && !$wasAlreadySold) {
            \App\Models\InvestmentLog::create([
                'investment_id' => $fresh->id,
                'user_id'       => $request->user()->id,
                'action'        => 'sell',
                'quantity'      => $fresh->quantity,
                'price'         => $fresh->sold_price ?? $fresh->current_price,
                'notes'         => 'Aset dijual seluruhnya (API)',
                'logged_at'     => ($fresh->sold_date ? $fresh->sold_date->format('Y-m-d') : now()->format('Y-m-d')) . ' ' . now()->format('H:i:s'),
            ]);
        }

        return response()->json(['success' => true, 'data' => $this->format($fresh)]);
    }

    public function destroy(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $investment->delete();
        return response()->json(['success' => true]);
    }

    private function format(Investment $i): array
    {
        $totalInvested = $i->quantity * $i->buy_price;
        $currentValue = $i->quantity * $i->current_price;
        $pnl = $currentValue - $totalInvested;
        $pnlPct = $totalInvested > 0 ? ($pnl / $totalInvested) * 100 : 0;
        
        return [
            'id'                => $i->id,
            'asset_name'        => $i->asset_name,
            'asset_type'        => $i->asset_type,
            'asset_type_label'  => Investment::assetTypeLabel($i->asset_type),
            'asset_type_icon'   => Investment::assetTypeIcon($i->asset_type),
            'quantity'          => (float) $i->quantity,
            'buy_price'         => (float) $i->buy_price,
            'current_price'     => (float) $i->current_price,
            'buy_date'          => $i->buy_date ? $i->buy_date->format('Y-m-d') : null,
            'total_invested'    => (float) $totalInvested,
            'current_value'     => (float) $currentValue,
            'pnl'               => (float) $pnl,
            'pnl_pct'           => (float) $pnlPct,
            'is_sold'           => (bool) $i->is_sold,
            'sold_price'        => $i->sold_price ? (float) $i->sold_price : null,
            'sold_date'         => $i->sold_date ? $i->sold_date->format('Y-m-d') : null,
            'notes'             => $i->notes,
        ];
    }
}
