<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::where('user_id', $request->user()->id)
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'   => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'type' => $c->type,
            ]);

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:20',
            'type' => 'required|in:income,expense,bill,saving,investment',
        ]);

        $category = Category::create([
            'user_id' => $request->user()->id,
            'name'    => $request->name,
            'icon'    => $request->icon ?? '📦',
            'type'    => $request->type,
        ]);

        return response()->json(['success' => true, 'data' => $category], 201);
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'icon' => 'nullable|string|max:20',
            'type' => 'sometimes|in:income,expense,bill,saving,investment',
        ]);

        $category->update($request->only('name', 'icon', 'type'));

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan.'], 404);
        }

        $category->delete();

        return response()->json(['success' => true, 'message' => 'Kategori dihapus.']);
    }
}
