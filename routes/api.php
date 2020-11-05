<?php

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

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
        1, 1.5, 2, 2.5, 3
    ];

    $percent = $data[$option];
    $order = PurchaseOrder::find($request->id);

    $total_price_items = $order->getPurchaseTotalItemPrice();

    $final = ($total_price_items * $percent) / 100;
    return $final;
});
