<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function kpis(Request $request)
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : Carbon::today()->startOfDay();
        $to   = $request->query('to')   ? Carbon::parse($request->query('to'))->endOfDay()   : Carbon::today()->endOfDay();

        $base = Transaction::where('user_id', $request->user()->id)
            ->whereBetween('happened_at', [$from, $to]);

        $income = (clone $base)->where('type','income')->sum('amount');
        $expense = (clone $base)->where('type','expense')->sum('amount');

        return response()->json([
            'range' => ['from' => $from->toDateTimeString(), 'to' => $to->toDateTimeString()],
            'income' => (float)$income,
            'expense' => (float)$expense,
            'profit' => (float)($income - $expense),
        ]);
    }
}
public function salesSeries(Request $request)
{
    $period = $request->query('period', 'week'); // week | month

    if ($period === 'month') {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $rows = \App\Models\Transaction::query()
            ->where('user_id', $request->user()->id)
            ->where('type', 'income')
            ->whereBetween('happened_at', [$from, $to])
            ->selectRaw('DATE(happened_at) as label, SUM(amount) as total')
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return response()->json([
            'period' => 'month',
            'series' => $rows,
        ]);
    }

    $from = now()->startOfWeek();
    $to = now()->endOfWeek();

    $rows = \App\Models\Transaction::query()
        ->where('user_id', $request->user()->id)
        ->where('type', 'income')
        ->whereBetween('happened_at', [$from, $to])
        ->selectRaw('DATE(happened_at) as label, SUM(amount) as total')
        ->groupBy('label')
        ->orderBy('label')
        ->get();

    return response()->json([
        'period' => 'week',
        'series' => $rows,
    ]);
}
public function expensesByCategory(Request $request)
{
    $from = $request->query('from')
        ? \Illuminate\Support\Carbon::parse($request->query('from'))->startOfDay()
        : now()->startOfMonth();

    $to = $request->query('to')
        ? \Illuminate\Support\Carbon::parse($request->query('to'))->endOfDay()
        : now()->endOfMonth();

    $rows = \App\Models\Transaction::query()
        ->with('category')
        ->where('user_id', $request->user()->id)
        ->where('type', 'expense')
        ->whereBetween('happened_at', [$from, $to])
        ->join('categories', 'transactions.category_id', '=', 'categories.id')
        ->selectRaw('categories.name as label, SUM(transactions.amount) as total')
        ->groupBy('categories.name')
        ->orderByDesc('total')
        ->get();

    return response()->json([
        'series' => $rows,
    ]);
}