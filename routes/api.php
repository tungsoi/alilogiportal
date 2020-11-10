<?php

use App\Models\Alilogi\TransportRecharge;
use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use App\User;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    $order_id = $request->order_id;
    DB::beginTransaction();

    try {

        // update trang thai don hang
        $order = PurchaseOrder::find($order_id);
        $order->status = PurchaseOrder::STATUS_CANCEL;
        $order->save();

        // update trang thai san pham
        OrderItem::whereOrderId($order_id)->update([
            'status'    =>  OrderItem::STATUS_PURCHASE_OUT_OF_STOCK
        ]);

        // tra lai coc cho khach
        $deposite = $order->deposited;
        $customer = User::find($order->customer_id);
        $wallet = $customer->wallet;
        $customer->wallet = $wallet + (int) $deposite;
        $customer->save();

        $data = [
            'customer_id'   =>  $order->customer_id,
            'user_id_created'   =>  $request->user_id_created,
            'money' =>  $deposite,
            'type_recharge' =>  TransportRecharge::REFUND,
            'content'   =>  'Hoàn lại tiền cọc đơn hàng ' . $order->order_number
        ];
        TransportRecharge::create($data);

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