<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CashSessionController extends Controller
{
    public function today(Request $request)
    {
        $date = Carbon::today()->toDateString();
        $cash = CashSession::where('user_id', $request->user()->id)
            ->where('date', $date)
            ->with('transactions')
            ->first();

        return response()->json(['cash_session' => $cash]);
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'opening_amount' => ['required','numeric','min:0'],
        ]);

        $date = Carbon::today()->toDateString();

        $cash = CashSession::firstOrCreate(
            ['user_id' => $request->user()->id, 'date' => $date],
            [
                'opening_amount' => $data['opening_amount'],
                'status' => 'open',
                'opened_at' => now(),
            ]
        );

        // Si existía pero estaba cerrada, no reabrimos en MVP:
        if ($cash->status === 'closed') {
            return response()->json(['message' => 'La caja de hoy ya está cerrada'], 409);
        }

        // Si existía y estaba open, actualizamos opening si estaba 0:
        if ($cash->opening_amount == 0) {
            $cash->update(['opening_amount' => $data['opening_amount']]);
        }

        return response()->json(['cash_session' => $cash]);
    }

    public function close(Request $request, CashSession $cashSession)
    {
        if ($cashSession->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ($cashSession->status === 'closed') {
            return response()->json(['message' => 'Caja ya cerrada'], 409);
        }

        $data = $request->validate([
            'closing_amount' => ['required','numeric','min:0'],
        ]);

        $cashSession->update([
            'closing_amount' => $data['closing_amount'],
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json(['cash_session' => $cashSession]);
    }
}