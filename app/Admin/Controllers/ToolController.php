<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderItem;

class ToolController extends Controller
{
    public function booking(Request $request)
    {
        $data = $request->data['selected'];
        $orderTempIds = $this->createProduct($data);
        $url = route(
            'orders.temps.show',
            [
                'id' => $orderTempIds->implode(','),
                'redirect_to' => urlencode(route('orders.temps.show', ['id' => $orderTempIds->implode(',')]))
            ]
        );

        return $url;
    }

    public function show(Request $request)
    {
        # code...
        $item_id = $request->id;
        return redirect()->route('admin.carts.addCart', $item_id);
    }

    public function show1688(Request $request) {
        dd($request->all());
    }

    public function createProduct(array $data)
    {
        $orderTempIds = [];
        
        if (isset($data[0]['site']) && $data[0]['site'] == '1688') {
            foreach ($data as $item) {
                if (!empty($item->price_range)) {
                    $newItemPrice = null;
                    $priceRange = $item->price_range;
                    foreach ($priceRange as $range) {
                        if ($range->end != null) {
                            if ($item->qty >= $range->begin && $item->qty <= $range->end) {
                                $newItemPrice = $range->price;
                            }
                        } elseif ($item->qty >= $range->begin) {
                            $newItemPrice = $range->price;
                        }
                    }
                    if (empty($newItemPrice)) {
                        $newItemPrice = $priceRange[0]->price;
                    }
                    $item['price'] = $newItemPrice;
                }
                unset($item['property']);
                $item['qty_reality'] = $item['qty'];
                $order_item = OrderItem::create($item);

                $orderTempIds[] = $order_item->id;
            }
        } else {
            if (!empty($data->price_range)) {
                $newItemPrice = null;
                $priceRange = $data->price_range;
                foreach ($priceRange as $range) {
                    if ($range->end != null) {
                        if ($data->qty >= $range->begin && $data->qty <= $range->end) {
                            $newItemPrice = $range->price;
                        }
                    } elseif ($data->qty >= $range->begin) {
                        $newItemPrice = $range->price;
                    }
                }
                if (empty($newItemPrice)) {
                    $newItemPrice = $priceRange[0]->price;
                }
                $data['price'] = $newItemPrice;
            }
            unset($data['property']);

            $data['qty_reality'] = $data['qty'];
            $order_item = OrderItem::create($data);
            $orderTempIds[] = $order_item->id;
        }
        return collect($orderTempIds);
    }


}