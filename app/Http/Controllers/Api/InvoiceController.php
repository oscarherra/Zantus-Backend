<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['supplier', 'category'])->orderBy('due_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(20);

        return response()->json(['invoices' => $invoices]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice = Invoice::create($data);

        return response()->json(['invoice' => $invoice], 201);
    }

    public function pay(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return response()->json(['message' => 'La factura ya está pagada'], 409);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json(['invoice' => $invoice->load(['supplier', 'category'])]);
    }

    public function upcoming(Request $request)
    {
        $days = (int) $request->query('days', 7);
        $today = Carbon::today();
        $limit = $today->copy()->addDays($days);

        $invoices = Invoice::with(['supplier', 'category'])
            ->where('status', 'pending')
            ->whereBetween('due_date', [$today, $limit])
            ->orderBy('due_date')
            ->get();

        return response()->json(['invoices' => $invoices]);
    }

    public function refreshStatuses()
    {
        Invoice::where('status', 'pending')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        return response()->json(['message' => 'Estados actualizados']);
    }
}