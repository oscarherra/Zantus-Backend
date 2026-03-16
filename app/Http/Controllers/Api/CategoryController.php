<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::where('is_active', true)->orderBy('name');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'categories' => $query->get()
        ]);
    }
}