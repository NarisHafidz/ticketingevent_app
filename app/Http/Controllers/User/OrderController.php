<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DetailOrder;
use App\Models\Order;
use App\Models\Tiket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $user = Auth::user(); 
        
        $orders = Order::where('user_id', $user->id)
            ->with('event')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load('detailOrders.tiket', 'event');

        return view('orders.show', compact('order'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => 'required|exists:events,id',
            'items' => 'required|array|min:1',
            'items.*.tiket_id' => 'required|exists:tikets,id',
            'items.*.jumlah' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'User belum login'
            ], 401);
        }

        try {
            $order = DB::transaction(function () use ($data, $user) {

                $total = 0;

                foreach ($data['items'] as $item) {
                    $tiket = Tiket::lockForUpdate()->findOrFail($item['tiket_id']);

                    if ($tiket->stok < $item['jumlah']) {
                        throw new \Exception("Stok tiket {$tiket->tipe} tidak mencukupi");
                    }

                    $total += ($tiket->harga ?? 0) * $item['jumlah'];
                }

                $order = Order::create([
                    'user_id' => $user->id,
                    'event_id' => $data['event_id'],
                    'order_date' => now(),
                    'total_harga' => $total,
                ]);

                foreach ($data['items'] as $item) {
                    $tiket = Tiket::findOrFail($item['tiket_id']);

                    DetailOrder::create([
                        'order_id' => $order->id,
                        'tiket_id' => $tiket->id,
                        'jumlah' => $item['jumlah'],
                        'subtotal_harga' => ($tiket->harga ?? 0) * $item['jumlah'],
                    ]);

                    $tiket->decrement('stok', $item['jumlah']); 
                }

                return $order;
            });

            return response()->json([
                'ok' => true,
                'order_id' => $order->id,
                'redirect' => route('orders.index')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}

