<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;

class SupplierController extends Controller
{
    public function index()
    {
        return response()->json([
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }
}