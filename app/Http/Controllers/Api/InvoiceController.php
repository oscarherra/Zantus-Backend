<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::query()->orderBy('due_date', 'asc');

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        return response()->json([
            'invoices' => $query->paginate(50)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:255',
            'category_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
        ]);

        $invoice = Invoice::create([
            'supplier_name' => $request->supplier_name,
            'category_name' => $request->category_name,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => 'pending',
        ]);

        return response()->json(['invoice' => $invoice]);
    }

    public function pay(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json(['invoice' => $invoice]);
    }

    public function refreshStatuses()
    {
        Invoice::where('status', 'pending')
               ->where('due_date', '<', now()->toDateString())
               ->update(['status' => 'overdue']);

        return response()->json(['message' => 'Statuses updated successfully']);
    }
}