<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('category')->latest('happened_at');

        if ($request->has('cash_session_id')) {
            $query->where('cash_session_id', $request->cash_session_id);
        }

        return response()->json([
            'transactions' => $query->paginate(50)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cash_session_id' => 'required|exists:cash_sessions,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,sinpe',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'required|string',
        ]);

        $transaction = Transaction::create([
            ...$request->all(),
            'happened_at' => now(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['transaction' => $transaction]);
    }
}