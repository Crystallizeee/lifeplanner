<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HabitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $today  = today();
        $habits = Habit::where('user_id', $request->user()->id)
            ->where('is_archived', false)
            ->orderBy('name')
            ->get()
            ->map(fn($h) => $this->format($h, $today));
        return response()->json(['success' => true, 'data' => $habits]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:20',
        ]);
        $habit = Habit::create([
            'user_id'    => $request->user()->id,
            'habit_name' => $request->name,
            'emoji'      => $request->icon ?? '✅',
        ]);
        return response()->json(['success' => true, 'data' => $this->format($habit, today())], 201);
    }

    public function show(Request $request, Habit $habit): JsonResponse
    {
        if ($habit->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        return response()->json(['success' => true, 'data' => $this->format($habit, today())]);
    }

    public function update(Request $request, Habit $habit): JsonResponse
    {
        if ($habit->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        
        $data = [];
        if ($request->has('name')) $data['habit_name'] = $request->name;
        if ($request->has('icon')) $data['emoji'] = $request->icon;
        if ($request->has('is_archived')) $data['is_archived'] = $request->is_archived;

        $habit->update($data);
        return response()->json(['success' => true, 'data' => $this->format($habit->fresh(), today())]);
    }

    public function destroy(Request $request, Habit $habit): JsonResponse
    {
        if ($habit->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $habit->delete();
        return response()->json(['success' => true]);
    }

    public function logToday(Request $request, Habit $habit): JsonResponse
    {
        if ($habit->user_id !== $request->user()->id) return response()->json(['success' => false], 404);

        $log = HabitLog::firstOrCreate(
            ['habit_id' => $habit->id, 'date' => today()],
            ['is_checked' => false]
        );
        $log->update(['is_checked' => !$log->is_checked]);

        // Update streak
        if ($log->is_checked) {
            $yesterday = HabitLog::where('habit_id', $habit->id)
                ->whereDate('date', today()->subDay())
                ->where('is_checked', true)->exists();
            $habit->increment('current_streak');
            if (!$yesterday) $habit->update(['current_streak' => 1]);
            if ($habit->current_streak > $habit->longest_streak) {
                $habit->update(['longest_streak' => $habit->current_streak]);
            }
        } else {
            $habit->update(['current_streak' => max(0, $habit->current_streak - 1)]);
        }

        return response()->json(['success' => true, 'data' => $this->format($habit->fresh(), today())]);
    }

    private function format(Habit $h, Carbon $today): array
    {
        $todayLog = HabitLog::where('habit_id', $h->id)->whereDate('date', $today)->first();
        return [
            'id'             => $h->id,
            'name'           => $h->habit_name,
            'icon'           => $h->emoji ?? '✅',
            'description'    => null,
            'frequency'      => 'daily',
            'current_streak' => $h->current_streak ?? 0,
            'longest_streak' => $h->longest_streak ?? 0,
            'checked_today'  => $todayLog ? (bool) $todayLog->is_checked : false,
            'is_archived'    => (bool) $h->is_archived,
        ];
    }
}
