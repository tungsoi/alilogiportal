<?php

use App\Models\Alilogi\TransportRecharge;
use App\Models\ExchangeRate;
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
        0, 1, 1.5, 2, 2.5, 3, 0.5
    ];

    $percent = $data[$option];
    $order = PurchaseOrder::find($request->id);

    $total_price_items = $order->getPurchaseTotalItemPrice();

    $final = ($total_price_items * $percent) / 100;
    return $final;
});

/**
 * Admin huy don hang cua khach hang
 */
Route::post('/cancle-purchase-order', function (Request $request) {
    DB::beginTransaction();

    try {
        if ($request->ajax()) {
            $order = PurchaseOrder::find($request->order_id);

            // update trang thai don hang
            $order->update([
                'status'    =>  PurchaseOrder::STATUS_CANCEL,
                'purchase_order_service_fee'    =>  0,
                'deposit_default'   =>  0,
                'purchase_total_items_price'    =>  0,
                'final_total_price' =>  0
            ]);


            // update trang thai cua san pham
            if ($order->items->count() > 0) {
                foreach ($order->items as $item) {
                    $item->qty_reality = 0;
                    $item->status = OrderItem::STATUS_PURCHASE_OUT_OF_STOCK;
                    $item->save();
                }
            }

            // check tien coc, neu  > 0 -> back lai tien
            if ($order->deposited) {
                $customer = User::find($order->customer_id); 
                $customer->wallet += $order->deposited;
                $customer->save();

                TransportRecharge::firstOrCreate([
                    'customer_id'   =>  $order->customer_id,
                    'user_id_created'   =>  $request->user_id_created,
                    'money' =>  $order->deposited,
                    'type_recharge' =>  TransportRecharge::REFUND,
                    'content'   =>  'Ho??n l???i ti???n c???c ????n h??ng ' . $order->order_number
                ]);
            }

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

/**
 * Admin xac nhan 1 don hang da dat hang tat ca san pham
 */
Route::post('/confirm-ordered', function (Request $request) {
    $order_id = $request->order_id;
    $user_id_created = $request->user_id_created;

    DB::beginTransaction();

    try {
        if ($request->ajax()) {
            $order = PurchaseOrder::find($order_id);
            $ordered_items = $order->totalOrderedItems();
            $total_items = $order->sumQtyRealityItem();
            if ($ordered_items != $total_items) {
                return response()->json([
                    'error' =>  true,
                    'msg'   =>  '????n h??ng n??y v???n c??n s???n ph???m ch??a ???????c ?????t h??ng. Vui l??ng x??c nh???n tr???ng th??i c???a t???t c??? s???n ph???m tr?????c.'
                ]);
            }
            else {
                $order->status = PurchaseOrder::STATUS_ORDERED;
                $order->user_id_confirm_ordered = $user_id_created;
                $order->order_at = now();
                $order->save();
    
                DB::commit();
                return response()->json([
                    'error' =>  false,
                    'msg'   =>  'success'
                ]);
            }
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

/**
 * Admin xac nhan san pham trong don da het hang
 */
Route::post('/confirm-outstock', function (Request $request) {

    // STATUS_NEW_ORDER : don hang moi
    // -> item -> het hang: qty_reality = 0
    // -> order -> tinh lai tien thuc dat, tong tien, tien coc mac dinh
    // STATUS_DEPOSITED_ORDERING : da coc - dang dat
    // -> item -> het hang: qty_reality = 0
    // -> order -> tinh lai tien thuc dat, tong tien, tien coc mac dinh
    // STATUS_ORDERED : da dat hang
    // -> item -> het hang: 
    // STATUS_SUCCESS : thanh cong
    // STATUS_CANCEL : da huy
    // DB::beginTransaction();

    try {
        if ($request->ajax()) {
            $item_id = $request->item_id;

            $item = OrderItem::find($item_id);
            $order = $item->order;

            $item->qty_reality = 0;
            $item->status = OrderItem::STATUS_PURCHASE_OUT_OF_STOCK;
            $item->save();

            $new_data = [
                'purchase_total_items_price'    =>  0, // tong tien thuc dat
                'purchase_cn_transport_fee'     =>  0, // tong tien ship noi dia
                'final_total_price'     =>  0, // tong gia cuoi
                'deposit_default' => 0, // can coc,
                'purchase_order_service_fee'    =>  0 // phi dich vu
            ];

            foreach ($order->items as $item) {
                if ($item->status != OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                    $new_data['purchase_cn_transport_fee'] += $item->purchase_cn_transport_fee; // tong phi van chuyen
                    $new_data['purchase_total_items_price'] += $item->qty_reality * $item->price; // tong tien thuc dat
                }
            }

            $rate = $order->current_rate;

            $percent = (float) PurchaseOrder::PERCENT_NUMBER[$order->customer->customer_percent_service];
            $new_data['purchase_order_service_fee'] = round($new_data['purchase_total_items_price'] / 100 * $percent, 2);
            $new_data['final_total_price'] = round( 
                ($new_data['purchase_total_items_price'] + $new_data['purchase_order_service_fee'] + $new_data['purchase_cn_transport_fee']) 
                * $rate
            );
            $new_data['deposit_default'] = round($new_data['final_total_price'] * 70 / 100);

            $order->update($new_data);
            $order->save();

            $flag = 0;
            foreach ($order->items as $item) {
                if ($item->status != OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                    $flag ++;
                }
            }

            if ($flag == 0) {
                $order->status = PurchaseOrder::STATUS_CANCEL;
                $order->save();

                $flag_deposite = User::find($order->customer_id)
                                ->update([
                                    'wallet'    =>  $order->customer->wallet + $order->deposited
                                ]);

                if ($flag_deposite) {
                    TransportRecharge::firstOrCreate([
                        'customer_id'   =>  $order->customer_id,
                        'user_id_created'   => $order->customer_id,
                        'money' =>  $order->deposited,
                        'type_recharge' =>  TransportRecharge::REFUND,
                        'content'   =>  'Ho??n ti???n do h???t h??ng s???n ph???m trong ????n. M?? ????n h??ng '.$order->order_number,
                        'order_type'    =>  TransportRecharge::TYPE_ORDER
                    ]);


                    // DB::commit();
                    return response()->json([
                        'error' =>  false,
                        'msg'   =>  'success'
                    ]);
                }
            }
 
            // DB::commit();
            return response()->json([
                'error' =>  false,
                'msg'   =>  'success'
            ]);
        }
    }
    catch (\Exception $e) {
        // DB::rollBack();
        return response()->json([
            'error' =>  true,
            'msg'   =>  $e->getMessage()
        ]);
    }
});

/**
 * Khach hang dat coc don hang
 */
Route::post('/customer-deposite', function (Request $request) {
    DB::beginTransaction();
    try {
        if ($request->ajax())
        {
            $data = $request->all();

            $order = PurchaseOrder::find($data['order_id']);

            if ($order->customer->wallet < $order->deposit_default) {
                return response()->json([
                    'error' =>  true,
                    'msg'   =>  'S??? d?? trong v?? c???a b???n kh??ng ????? ????? thanh to??n. Vui l??ng li??n h??? b??? ph???n Sale ????? n???p ti???n v??o t??i kho???n.'
                ]); 
            }
            else {
                $flag_update = PurchaseOrder::find($data['order_id'])
                ->update([
                    'deposited' =>  $order->deposit_default,
                    'user_id_deposited' =>  $order->customer_id,
                    'deposited_at'  =>  date('Y-m-d H:i:s', strtotime(now())),
                    'status'    =>  PurchaseOrder::STATUS_DEPOSITED_ORDERING
                ]);

                if ($flag_update) {
                    $flag_deposite = User::find($order->customer_id)
                    ->update([
                        'wallet'    =>  $order->customer->wallet - $order->deposit_default
                    ]);

                    if ($flag_deposite) {
                        TransportRecharge::firstOrCreate([
                            'customer_id'   =>  $order->customer_id,
                            'user_id_created'   => $order->customer_id,
                            'money' =>  $order->deposit_default,
                            'type_recharge' =>  TransportRecharge::DEDUCTION,
                            'content'   =>  '?????t c???c ????n h??ng mua h???. M?? ????n h??ng '.$order->order_number,
                            'order_type'    =>  TransportRecharge::TYPE_ORDER
                        ]);


                        DB::commit();
                        return response()->json([
                            'error' =>  false,
                            'msg'   =>  'success'
                        ]);
                    }

                    DB::rollBack();
                    return response()->json([
                        'error' =>  false,
                        'msg'   =>  'success'
                    ]);
                }

                DB::rollBack();
                return response()->json([
                    'error' =>  false,
                    'msg'   =>  'success'
                ]);
                
            }

            return $order;
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

/**
 * Kh??ch h??ng xo?? ????n h??ng m???i
 */
Route::post('/customer-destroy', function (Request $request) {
    DB::beginTransaction();
    try {
        if ($request->ajax()) {
            PurchaseOrder::find($request->order_id)->delete();
            OrderItem::where('order_id', $request->order_id)->delete();
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

/**
 * Kh??ch h??ng xo?? s???n ph???m kh???i ????n h??ng
 */
Route::post('/customer-delete-item-from-order', function (Request $request) {
    DB::beginTransaction();
    try {
        if ($request->ajax()) {
            $item = OrderItem::find($request->id);
            $order_id = $item->order_id;
            $order = PurchaseOrder::find($order_id);

            if ($order->items->count() == 1) {
                return response()->json([
                    'error' =>  true,
                    'msg'   =>  '????n h??ng n??y c?? duy nh???t 1 s???n ph???m. B???n kh??ng th??? xo?? s???n ph???m n??y kh???i ????n h??ng.'
                ]);
            }

            $flag = $item->delete();

            if ($flag) {
                $new_data = [
                    'purchase_total_items_price'    =>  0, // tong tien thuc dat
                    'purchase_cn_transport_fee'     =>  0, // tong tien ship noi dia
                    'final_total_price'     =>  0, // tong gia cuoi
                    'deposit_default' => 0, // can coc,
                    'purchase_order_service_fee'    =>  0 // phi dich vu
                ];

                foreach ($order->items as $item) {
                    if ($item->status != OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                        $new_data['purchase_cn_transport_fee'] += $item->purchase_cn_transport_fee;
                        $new_data['purchase_total_items_price'] += $item->qty_reality * $item->price;
                    }
                }

                $rate = ExchangeRate::first()->vnd;

                $percent = (float) PurchaseOrder::PERCENT_NUMBER[$order->customer->customer_percent_service];
                $new_data['purchase_order_service_fee'] = round($new_data['purchase_total_items_price'] / 100 * $percent, 2);
                $new_data['final_total_price'] = round( ($new_data['purchase_total_items_price'] ) * $rate);
                $new_data['deposit_default'] = round($new_data['final_total_price'] * 70 / 100);

                $order->update($new_data);
                $order->save();
                
                DB::commit();
                return response()->json([
                    'error' =>  false,
                    'msg'   =>  'success'
                ]);
            }
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



/**
 * K??? to??n ch???t ????n th??nh c??ng b???ng tay
 */
Route::post('/confirm-order-success', function (Request $request) {
    DB::beginTransaction();
    try {
        if ($request->ajax()) {
            $order_id = $request->id;
            $order = PurchaseOrder::find($order_id);

            if ($order->status != PurchaseOrder::STATUS_SUCCESS) {
                $order->status = PurchaseOrder::STATUS_SUCCESS;
                $order->success_at = date('Y-m-d H:i:s', strtotime(now()));
                $order->save();

                if ($order->status == PurchaseOrder::STATUS_SUCCESS) {

                    $deposited = $order->deposited; // s??? ti???n ???? c???c
                    $total_final_price = round($order->totalBill() * $order->current_rate); // t???ng ti???n ????n hi???n t???i VND

                    $customer = $order->customer;
                    $flag = false;
                    if ($deposited <= $total_final_price) {
                        $money = $total_final_price - $deposited;
                        $customer->wallet -= $money;
                        $customer->save();
                        $msg = 'Thanh to??n ????n h??ng mua h???. M?? ????n h??ng '.$order->order_number;
                        $flag = true;

                        if ($flag) {
                            TransportRecharge::firstOrCreate([
                                'customer_id'       =>  $order->customer_id,
                                'user_id_created'   =>  1,
                                'money'             =>  $money,
                                'type_recharge'     =>  TransportRecharge::DEDUCTION,
                                'content'           =>  $msg,
                                'order_type'        =>  TransportRecharge::TYPE_ORDER
                            ]);
                        }
                    } else {
                        $money = $deposited - $total_final_price;
                        $customer->wallet += $money;
                        $customer->save();
                        $msg = 'Thanh to??n ????n h??ng mua h???. M?? ????n h??ng '.$order->order_number;
                        $flag = true;

                        if ($flag) {
                            TransportRecharge::firstOrCreate([
                                'customer_id'       =>  $order->customer_id,
                                'user_id_created'   =>  1,
                                'money'             =>  $money,
                                'type_recharge'     =>  TransportRecharge::REFUND,
                                'content'           =>  $msg,
                                'order_type'        =>  TransportRecharge::TYPE_ORDER
                            ]);
                        }
                    }
                    
                    DB::commit();
                    return response()->json([
                        'error' =>  false,
                        'msg'   =>  'success'
                    ]);
                }
            }
        }
        
    }
    catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' =>  true,
            'msg'   =>  $e
        ]);
    }
});