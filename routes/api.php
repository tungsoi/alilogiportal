<?php

use App\Models\Alilogi\TransportRecharge;
use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use App\User;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Null_;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/service_percent', function (Request $request) {
    $option = $request->q;
    $data = [
        0, 1, 1.5, 2, 2.5, 3
    ];

    $percent = $data[$option];
    $order = PurchaseOrder::find($request->id);

    $total_price_items = $order->getPurchaseTotalItemPrice();

    $final = ($total_price_items * $percent) / 100;
    return $final;
});

Route::post('/cancle-purchase-order', function (Request $request) {
    DB::beginTransaction();
    $order_id = $request->order_id;

    try {

        $order = PurchaseOrder::find($order_id);

        $status = $order->status;

        # don hang moi
        if ($status == PurchaseOrder::STATUS_NEW_ORDER)
        {
           # order
            $order->status = PurchaseOrder::STATUS_CANCEL;
            $order->save();

            # item
            foreach ($order->items as $item)
            {
                $item->status = OrderItem::STATUS_PURCHASE_OUT_OF_STOCK;
                $item->save();
            }
        } 
        else if ($status == PurchaseOrder::STATUS_DEPOSITED_ORDERING)
        {
            # order
            $order->status = PurchaseOrder::STATUS_CANCEL;
            $order->save();

            # item
            foreach ($order->items as $item)
            {
                $item->status = OrderItem::STATUS_PURCHASE_OUT_OF_STOCK;
                $item->save();
            }

            $deposite = $order->deposited;

            $flag = TransportRecharge::firstOrCreate([
                'customer_id'   =>  $order->customer_id,
                'user_id_created'   =>  $request->user_id_created,
                'money' =>  $deposite,
                'type_recharge' =>  TransportRecharge::REFUND,
                'content'   =>  'Hoàn lại tiền cọc đơn hàng ' . $order->order_number
            ]);

            if ($flag) {
                $customer = $order->customer;
                $customer->wallet += $deposite;
                $customer->save();
            }
        }
        else {
            return response()->json([
                'error' =>  false,
                'msg'   =>  'Không được phép huỷ đơn hàng này'
            ]);
        }

        DB::commit();

        return response()->json([
            'error' =>  false,
            'msg'   =>  'success'
        ]);
    } 
    catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' =>  true,
            'msg'   =>  $e->getMessage()
        ]);
    }
});

Route::post('/confirm-ordered', function (Request $request) {
    $order_id = $request->order_id;
    $user_id_created = $request->user_id_created;

    DB::beginTransaction();

    try {
        $order = PurchaseOrder::find($order_id);
        $ordered_items = $order->totalOrderedItems();
        $total_items = $order->sumQtyRealityItem();
        if ($ordered_items != $total_items) {
            return response()->json([
                'error' =>  true,
                'msg'   =>  'Đơn hàng này vẫn còn sản phẩm chưa được đặt hàng. Vui lòng xác nhận trạng thái của tất cả sản phẩm trước.'
            ]);
        }
        else {
            $order->status = PurchaseOrder::STATUS_ORDERED;
            $order->user_id_confirm_ordered = $user_id_created;
            $order->save();

            DB::commit();
            return response()->json([
                'error' =>  false,
                'msg'   =>  'success'
            ]);
        }
    }
    catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' =>  true,
            'msg'   =>  $e->getMessage()
        ]);
    }
});


Route::post('/confirm-outstock', function (Request $request) {
    $item_id = $request->item_id;

    DB::beginTransaction();

    try {
        $item = OrderItem::find($item_id);
        $item->qty_reality = 0;
        $item->status = OrderItem::STATUS_PURCHASE_OUT_OF_STOCK;
        $item->save();

        DB::commit();
        return response()->json([
            'error' =>  false,
            'msg'   =>  'success'
        ]);
    }
    catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' =>  true,
            'msg'   =>  $e->getMessage()
        ]);
    }
});

Route::post('/customer-deposite', function (Request $request) {
    try {
        $order = PurchaseOrder::find($request->order_id);
        $customer = $order->customer;
        $deposited = $order->deposited;
        $deposite = $order->deposit_default;

        if ($deposited == "") {
            if ($customer->wallet < $deposited) {
                return response()->json([
                    'error' =>  true,
                    'msg'   =>  'Số dư trong ví của bạn không đủ để thanh toán. Vui lòng liên hệ bộ phận Sale để nạp tiền vào tài khoản.'
                ]); 
            }
            else {
                $order->deposited = $deposite;
                $order->user_id_deposited = $order->customer_id;
                $order->deposited_at =  date('Y-m-d', strtotime(now()));
                $order->status = PurchaseOrder::STATUS_DEPOSITED_ORDERING;
                $order->save();

                $customer->wallet -= $deposite;
                $customer->save();

                TransportRecharge::firstOrCreate([
                    'customer_id'   =>  $order->customer_id,
                    'user_id_created'   => $order->customer_id,
                    'money' =>  $deposite,
                    'type_recharge' =>  TransportRecharge::DEPOSITE_ORDER,
                    'content'   =>  'Đặt cọc đơn hàng mua hộ. Mã đơn hàng '.$order->order_number,
                    'order_type'    =>  TransportRecharge::TYPE_ORDER
                ]);
        
                return response()->json([
                    'error' =>  false,
                    'msg'   =>  'success'
                ]);
            }
        }

        return response()->json([
            'error' =>  true,
            'msg'   =>  'Đơn hàng này đã đặt cọc.'
        ]);
    }
    catch (\Exception $e) {
        return response()->json([
            'error' =>  true,
            'msg'   =>  $e->getMessage()
        ]);
    }
});

Route::post('/customer-destroy', function (Request $request) {
    DB::beginTransaction();
    try {
        PurchaseOrder::find($request->order_id)->delete();
        OrderItem::where('order_id', $request->order_id)->delete();
        DB::commit();

        return response()->json([
            'error' =>  false,
            'msg'   =>  'success'
        ]);

    }
    catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' =>  true,
            'msg'   =>  $e->getMessage()
        ]);
    }
});


Route::post('/customer-delete-item-from-cart', function (Request $request) {
    DB::beginTransaction();
    try {
        OrderItem::find($request->id)->delete();
        DB::commit();

        return response()->json([
            'error' =>  false,
            'msg'   =>  'success'
        ]);

    }
    catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' =>  true,
            'msg'   =>  $e->getMessage()
        ]);
    }
});


Route::post('/customer-delete-item-from-order', function (Request $request) {
    
    try {
        $item = OrderItem::find($request->id);
        if ($item) {
            $order = PurchaseOrder::find($item->order_id);

            $item->delete();
    
            $current_items = $order->items;
            $res = [
                'purchase_total_items_price'    =>  0,
                'purchase_cn_transport_fee'     =>  0,
                'final_total_price'     =>  0,
                'deposit_default' => 0
            ];
    
            if ($current_items->count() > 0)
            {
                $purchase_total_items_price = 0; // tong tien sp
                $purchase_cn_transport_fee = 0; // tong tien van chuyen
                foreach ($current_items as $current_item) {
                    if ($current_item->status != OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                        $res['purchase_cn_transport_fee'] += $item->purchase_cn_transport_fee;
                        $res['purchase_total_items_price'] += $item->qty_reality * $item->price;
                    }
                }
        
                $res['final_total_price'] = ($res['purchase_total_items_price'] + $res['purchase_cn_transport_fee'] + $order->purchase_order_service_fee);
                $res['deposit_default'] = $res['final_total_price'] * 70 / 100;
            }
            
            $order->update($res);
            $order->save();
    
            return response()->json([
                'error' =>  false,
                'msg'   =>  'success'
            ]);
        }
    }
    catch (\Exception $e) {
        
        return response()->json([
            'error' =>  true,
            'msg'   =>  $e->getMessage()
        ]);
    }
});