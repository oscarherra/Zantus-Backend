<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function customers()
    {
        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'customers' => $customers
        ]);
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'notes' => $request->notes,
            'is_active' => true,
        ]);

        return response()->json([
            'customer' => $customer
        ], 201);
    }

    public function index(Request $request)
    {
        $query = Credit::with(['customer', 'user', 'payments'])
            ->orderByDesc('credited_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('credited_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('credited_at', '<=', $request->to);
        }

        return response()->json([
            'credits' => $query->paginate(50)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'cash_session_id' => 'nullable|exists:cash_sessions,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        $cashSessionId = $request->cash_session_id;

        if ($cashSessionId) {
            $cashSession = CashSession::findOrFail($cashSessionId);

            if ($cashSession->status !== 'open') {
                return response()->json([
                    'message' => 'No se puede registrar un fiado en una caja cerrada.'
                ], 400);
            }
        }

        $credit = Credit::create([
            'customer_id' => $request->customer_id,
            'cash_session_id' => $cashSessionId,
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'paid_amount' => 0,
            'description' => $request->description,
            'status' => 'pending',
            'credited_at' => now(),
        ]);

        return response()->json([
            'credit' => $credit->load(['customer', 'user', 'payments'])
        ], 201);
    }

    public function pay(Request $request, Credit $credit)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'cash_session_id' => 'nullable|exists:cash_sessions,id',
            'payment_method' => 'required|in:cash,card,sinpe',
            'register_income' => 'nullable|boolean',
        ]);

        if ($credit->status === 'paid') {
            return response()->json([
                'message' => 'Este fiado ya está pagado.'
            ], 400);
        }

        $remaining = $credit->amount - $credit->paid_amount;

        if ($request->amount > $remaining) {
            return response()->json([
                'message' => 'El abono no puede ser mayor al saldo pendiente.'
            ], 400);
        }

        $cashSession = null;

        if ($request->cash_session_id) {
            $cashSession = CashSession::findOrFail($request->cash_session_id);

            if ($cashSession->status !== 'open') {
                return response()->json([
                    'message' => 'No se puede registrar un pago en una caja cerrada.'
                ], 400);
            }
        }

        CreditPayment::create([
            'credit_id' => $credit->id,
            'cash_session_id' => $request->cash_session_id,
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'paid_at' => now(),
        ]);

        $newPaidAmount = $credit->paid_amount + $request->amount;

        $newStatus = 'partial';

        if ($newPaidAmount >= $credit->amount) {
            $newStatus = 'paid';
        }

        $credit->update([
            'paid_amount' => $newPaidAmount,
            'status' => $newStatus,
        ]);

        if ($request->boolean('register_income') && $cashSession) {
            Transaction::create([
                'cash_session_id' => $cashSession->id,
                'user_id' => $request->user()->id,
                'type' => 'income',
                'category_id' => null,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'description' => 'Pago de fiado: ' . $credit->customer->name,
                'happened_at' => now(),
            ]);
        }

        return response()->json([
            'credit' => $credit->fresh()->load(['customer', 'user', 'payments'])
        ]);
    }

    public function summary()
    {
        $pendingTotal = Credit::whereIn('status', ['pending', 'partial'])
            ->selectRaw('SUM(amount - paid_amount) as total')
            ->value('total') ?? 0;

        $weekTotal = Credit::whereBetween('credited_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
            ->sum('amount');

        $paidThisWeek = CreditPayment::whereBetween('paid_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
            ->sum('amount');

        $byCustomer = Credit::with('customer')
            ->whereIn('status', ['pending', 'partial'])
            ->get()
            ->groupBy('customer_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'customer_id' => $first->customer_id,
                    'customer_name' => $first->customer->name,
                    'pending_total' => $items->sum(function ($credit) {
                        return $credit->amount - $credit->paid_amount;
                    }),
                ];
            })
            ->values();

        return response()->json([
            'pending_total' => (float) $pendingTotal,
            'week_total' => (float) $weekTotal,
            'paid_this_week' => (float) $paidThisWeek,
            'by_customer' => $byCustomer,
        ]);
    }
}