<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingItem;
use Illuminate\Http\Request;

class ShoppingItemController extends Controller
{
    public function index(Request $request)
    {
        $items = ShoppingItem::where('user_id', $request->user()->id)
                             ->orderBy('created_at', 'desc')
                             ->get();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $item = ShoppingItem::create([
            'user_id'  => $request->user()->id,
            'name'     => $request->name,
            'quantity' => $request->quantity,
            'unit'     => $request->unit,
            'notes'    => $request->notes,
            'category' => $request->category ?? 'general',
            'status'   => 'pending',
        ]);

        return response()->json(['item' => $item], 201);
    }

    public function update(Request $request, ShoppingItem $shoppingItem)
    {
        $shoppingItem->update(
            $request->only(['status', 'name', 'quantity', 'unit', 'notes', 'category'])
        );

        return response()->json(['item' => $shoppingItem]);
    }

    public function clearDone(Request $request)
    {
        ShoppingItem::where('user_id', $request->user()->id)
                    ->where('status', 'done')
                    ->delete();

        return response()->json(['message' => 'Artículos comprados eliminados']);
    }

    public function destroy(ShoppingItem $shoppingItem)
    {
        $shoppingItem->delete();

        return response()->json(['message' => 'Artículo eliminado']);
    }
}