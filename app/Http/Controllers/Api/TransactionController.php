<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
    'cash_session_id' => ['nullable','integer','exists:cash_sessions,id'],
    'type' => ['required','in:income,expense'],
    'category_id' => ['required','exists:categories,id'],
    'amount' => ['required','numeric','min:0.01'],
    'payment_method' => ['required','in:cash,sinpe,card'],
    'description' => ['nullable','string','max:200'],
    'happened_at' => ['nullable','date'],
]);

        // Validar propiedad de la caja (si viene)
        if (!empty($data['cash_session_id'])) {
            $cash = CashSession::findOrFail($data['cash_session_id']);
            if ($cash->user_id !== $request->user()->id) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
        }

        $trx = Transaction::create([
            ...$data,
            'user_id' => $request->user()->id,
            'happened_at' => $data['happened_at'] ?? now(),
        ]);

        return response()->json(['transaction' => $trx], 201);
    }

    public function index(Request $request)
    {
        $q = Transaction::with('category')
    ->where('user_id', $request->user()->id)
    ->orderByDesc('happened_at');

        if ($request->filled('cash_session_id')) {
            $q->where('cash_session_id', $request->cash_session_id);
        }

        return response()->json([
            'transactions' => $q->paginate(20),
        ]);
    }
}