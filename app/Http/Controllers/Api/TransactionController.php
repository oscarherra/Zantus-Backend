<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('category')
            ->latest('happened_at');

        if ($request->filled('cash_session_id')) {
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

        $cashSession = CashSession::findOrFail($request->cash_session_id);

        if ($cashSession->status !== 'open') {
            return response()->json([
                'message' => 'No se pueden registrar movimientos en una caja cerrada.'
            ], 400);
        }

        $transaction = Transaction::create([
            'cash_session_id' => $cashSession->id,
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'description' => $request->description,
            'happened_at' => now(),
        ]);

        return response()->json([
            'transaction' => $transaction->load('category')
        ], 201);
    }
}