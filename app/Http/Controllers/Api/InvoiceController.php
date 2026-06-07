<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->refreshOverdueInvoices();

        $query = Invoice::with(['supplier', 'paidBy'])
            ->orderBy('due_date', 'asc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        return response()->json([
            'invoices' => $query->paginate(50)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'required_without:supplier_id|nullable|string|max:255',
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'issue_date' => 'nullable|date',
            'due_date' => 'required|date',
        ]);

        $invoice = Invoice::create([
            'supplier_id' => $request->supplier_id,
            'supplier_name' => $request->supplier_name,
            'category_name' => $request->category_name,
            'description' => $request->description,
            'amount' => $request->amount,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'status' => 'pending',
        ]);

        return response()->json([
            'invoice' => $invoice->load(['supplier', 'paidBy'])
        ], 201);
    }

    public function pay(Request $request, Invoice $invoice)
    {
        $request->validate([
            'register_expense' => 'nullable|boolean',
            'cash_session_id' => 'nullable|exists:cash_sessions,id',
            'payment_method' => 'nullable|in:cash,card,sinpe',
        ]);

        if ($invoice->status === 'paid') {
            return response()->json([
                'message' => 'Esta factura ya está pagada.'
            ], 400);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => $request->user()->id,
        ]);

        if ($request->boolean('register_expense')) {
            $cashSession = CashSession::find($request->cash_session_id);

            if (!$cashSession || $cashSession->status !== 'open') {
                return response()->json([
                    'message' => 'La factura se marcó como pagada, pero no se registró como gasto porque no hay una caja abierta válida.',
                    'invoice' => $invoice->fresh()->load(['supplier', 'paidBy']),
                ], 200);
            }

            $category = Category::firstOrCreate(
                [
                    'name' => $invoice->category_name,
                    'type' => 'expense',
                ],
                [
                    'is_active' => true,
                ]
            );

            Transaction::create([
                'cash_session_id' => $cashSession->id,
                'user_id' => $request->user()->id,
                'type' => 'expense',
                'category_id' => $category->id,
                'amount' => $invoice->amount,
                'payment_method' => $request->payment_method ?? 'cash',
                'description' => 'Pago de factura: ' . ($invoice->supplier?->name ?? $invoice->supplier_name ?? 'Proveedor sin nombre'),
                'happened_at' => now(),
            ]);
        }

        return response()->json([
            'invoice' => $invoice->fresh()->load(['supplier', 'paidBy'])
        ]);
    }

    public function cancel(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return response()->json([
                'message' => 'No se puede cancelar una factura que ya fue pagada.'
            ], 400);
        }

        $invoice->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'invoice' => $invoice
        ]);
    }

    public function upcoming()
    {
        $this->refreshOverdueInvoices();

        $invoices = Invoice::with('supplier')
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'invoices' => $invoices
        ]);
    }

    public function refreshStatuses()
    {
        $this->refreshOverdueInvoices();

        return response()->json([
            'message' => 'Estados actualizados correctamente'
        ]);
    }

    private function refreshOverdueInvoices(): void
    {
        Invoice::where('status', 'pending')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update([
                'status' => 'overdue'
            ]);
    }
}