<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderSingleResource;
use App\Models\Order;

class OrderController extends Controller {
    public function index() {
        $orders = Order::with('user')->paginate();
        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        return new OrderSingleResource($order);
    }

    public function change_status(Order $order)
    {
        if(request()->filled('status')){
            $status=request()->input('status');
            switch ($status){
                case Order::CANCELED:
                    $update_item=$order->update([
                        'status'=>Order::CANCELED
                    ]);
                    if($update_item){
                        return response()->json([
                            'status'=>true,
                            'item'=>new OrderSingleResource($order),
                        ]);
                    }
                    break;
                case Order::PROCESSING:
                    $update_item=$order->update([
                        'status'=>Order::PROCESSING
                    ]);
                    if($update_item){
                        return response()->json([
                            'status'=>true,
                            'item'=>new OrderSingleResource($order),
                        ]);
                    }
                    break;
                case Order::FINISHED:
                    $update_item=$order->update([
                        'status'=>Order::FINISHED
                    ]);
                    if($update_item){
                        return response()->json([
                            'status'=>true,
                            'item'=>new OrderSingleResource($order),
                        ]);
                    }
                    break;
            }
        }
    }
}
