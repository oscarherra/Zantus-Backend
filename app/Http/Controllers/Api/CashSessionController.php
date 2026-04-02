<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    public function today(Request $request)
    {
        $cash = CashSession::whereDate('opened_at', now()->toDateString())
                           ->latest('id')
                           ->first();

        return response()->json(['cash_session' => $cash]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
        ]);

        $existing = CashSession::where('status', 'open')->first();

        if ($existing) {
            return response()->json([
                'message' => 'Ya existe una caja abierta en este momento. Ciérrela primero.'
            ], 400);
        }

        $cash = CashSession::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'date'    => now()->toDateString(),
            ],
            [
                'opened_at'      => now(),
                'opening_amount' => $request->opening_amount,
                'status'         => 'open',
            ]
        );

        return response()->json(['cash_session' => $cash]);
    }

    public function close(Request $request, CashSession $cashSession)
    {
        $request->validate([
            'closing_amount' => 'required|numeric|min:0',
        ]);

        if ($cashSession->status !== 'open') {
            return response()->json(['message' => 'Esta caja ya está cerrada'], 400);
        }

        $cashSession->update([
            'closed_at'      => now(),
            'closing_amount' => $request->closing_amount,
            'status'         => 'closed',
        ]);

        return response()->json(['cash_session' => $cashSession]);
    }
}