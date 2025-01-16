<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Jobs\ProcessedSubscriptionOrders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // validatons
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'address' => 'required|string',
            'basket' => 'required|array|min:1',
            'basket.*.name' => 'required|string',
            'basket.*.type' => 'required|string|in:unit,subscription',
            'basket.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'address' => $validated['address'],
            ]);
            
            foreach ($validated['basket'] as $item) {
                $order->items()->create($item);
            
                if ($item['type'] === 'subscription') {
                    Log::info("Dispatching job for subscription: {$item['name']}");
                    ProcessedSubscriptionOrders::dispatch(
                        $item['name'],
                        $item['price']
                    );
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Order processed successfully',
                'order_id' => $order->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}