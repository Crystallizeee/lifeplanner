<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeightLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeightLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = WeightLog::where('user_id', $request->user()->id)
            ->orderByDesc('date')
            ->get()
            ->map(fn($l) => $this->format($l));
        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date'      => 'required|date',
            'weight_kg' => 'required|numeric|min:1',
            'notes'     => 'nullable|string|max:1000',
        ]);

        $log = WeightLog::updateOrCreate(
            ['user_id' => $request->user()->id, 'date' => $request->date],
            ['weight_kg' => $request->weight_kg, 'notes' => $request->notes]
        );

        return response()->json(['success' => true, 'data' => $this->format($log)], 201);
    }

    public function show(Request $request, WeightLog $weightLog): JsonResponse
    {
        if ($weightLog->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        return response()->json(['success' => true, 'data' => $this->format($weightLog)]);
    }

    public function update(Request $request, WeightLog $weightLog): JsonResponse
    {
        if ($weightLog->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        
        $request->validate([
            'date'      => 'sometimes|date',
            'weight_kg' => 'sometimes|numeric|min:1',
            'notes'     => 'nullable|string|max:1000',
        ]);

        $weightLog->update($request->only(['date', 'weight_kg', 'notes']));
        return response()->json(['success' => true, 'data' => $this->format($weightLog->fresh())]);
    }

    public function destroy(Request $request, WeightLog $weightLog): JsonResponse
    {
        if ($weightLog->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $weightLog->delete();
        return response()->json(['success' => true]);
    }

    private function format(WeightLog $l): array
    {
        return [
            'id'        => $l->id,
            'date'      => $l->date->format('Y-m-d'),
            'weight_kg' => (float) $l->weight_kg,
            'notes'     => $l->notes,
        ];
    }
}
