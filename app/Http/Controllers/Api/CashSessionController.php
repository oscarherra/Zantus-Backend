<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    /**
     * Devuelve la caja abierta actualmente.
     * Ya no usamos "today" como lógica principal, porque puede haber
     * varias aperturas y cierres en un mismo día.
     */
    public function current(Request $request)
    {
        $cash = CashSession::where('status', 'open')
            ->latest('opened_at')
            ->first();

        return response()->json([
            'cash_session' => $cash
        ]);
    }

    /**
     * Lista el historial de cajas.
     * Sirve para HistorialCajaView.vue, porque ahí el frontend llama a /cash-sessions.
     */
    public function index(Request $request)
    {
        $query = CashSession::with('user')
            ->orderByDesc('opened_at');

        if ($request->filled('from')) {
            $query->whereDate('opened_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('opened_at', '<=', $request->to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'cash_sessions' => $query->get()
        ]);
    }

    /**
     * Abre una nueva sesión de caja.
     * Importante: usamos create(), no updateOrCreate().
     */
    public function open(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
        ]);

        // Como es una única caja física, solo puede existir una caja abierta a la vez.
        $existing = CashSession::where('status', 'open')->first();

        if ($existing) {
            return response()->json([
                'message' => 'Ya existe una caja abierta en este momento. Ciérrela primero.'
            ], 400);
        }

        $cash = CashSession::create([
            'user_id' => $request->user()->id,
            'date' => now()->toDateString(),
            'opened_at' => now(),
            'opening_amount' => $request->opening_amount,
            'status' => 'open',
        ]);

        return response()->json([
            'cash_session' => $cash
        ], 201);
    }

    /**
     * Cierra la sesión de caja.
     * Calcula cuánto debería haber en efectivo y la diferencia.
     */
    public function close(Request $request, CashSession $cashSession)
    {
        $request->validate([
            'closing_amount' => 'required|numeric|min:0',
        ]);

        if ($cashSession->status !== 'open') {
            return response()->json([
                'message' => 'Esta caja ya está cerrada'
            ], 400);
        }

        $cashIncome = $cashSession->transactions()
            ->where('type', 'income')
            ->where('payment_method', 'cash')
            ->sum('amount');

        $cashExpense = $cashSession->transactions()
            ->where('type', 'expense')
            ->where('payment_method', 'cash')
            ->sum('amount');

        $expectedAmount = $cashSession->opening_amount + $cashIncome - $cashExpense;
        $differenceAmount = $request->closing_amount - $expectedAmount;

        $cashSession->update([
            'closed_at' => now(),
            'closing_amount' => $request->closing_amount,
            'expected_amount' => $expectedAmount,
            'difference_amount' => $differenceAmount,
            'status' => 'closed',
        ]);

        return response()->json([
            'cash_session' => $cashSession->fresh()
        ]);
    }
}