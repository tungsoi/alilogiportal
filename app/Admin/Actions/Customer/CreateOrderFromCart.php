<?php

namespace App\Admin\Actions\Customer;

use App\Admin\Services\OrderService;
use App\Models\Alilogi\Warehouse;
use App\Models\ExchangeRate;
use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CreateOrderFromCart extends BatchAction
{
    protected $selector = ".create-order";

    public $name = 'Tạo đơn hàng';

    public function handle(Collection $collection, Request $request)
    {
        $service = new OrderService();
        $order_number = $service->generateOrderNR();

        $exchange_rate = ExchangeRate::first()->vnd;

        $order = PurchaseOrder::create([
            'order_number'  =>  $order_number,
            'customer_id'   =>  Admin::user()->id,
            'order_type'    =>  1, // order // 2: transport
            'warehouse_id'  =>  $request->warehouse_id[0],
            'current_rate'  =>  $exchange_rate,
            'status'    =>  PurchaseOrder::STATUS_NEW_ORDER,
            'customer_name' =>  Admin::user()->symbol_name
        ]);

        $purchase_total_items_price = 0;
        $deposit_default = 0;
        foreach ($collection as $model) {
            OrderItem::find($model->id)->update([
                'order_id'  =>  $order->id,
                'status'    =>  OrderItem::STATUS_PURCHASE_ITEM_NOT_ORDER
            ]);

            $purchase_total_items_price += $model->price;
        }

        $final_total_price = $purchase_total_items_price * (int) $exchange_rate;
        $deposit_default    =   $final_total_price * 70 / 100;

        PurchaseOrder::find($order->id)->update([
            'purchase_total_items_price'    =>  $purchase_total_items_price,
            'final_total_price'             =>  $final_total_price,
            'deposit_default'   =>  $deposit_default
        ]);

        admin_success('Tạo đơn hàng thành công. Vui lòng liên hệ với bộ phận Sale để tiến hành đặt cọc cho đơn hàng này.');
        return $this->response()->success('Tạo đơn hàng thành công')->refresh();
    }

    public function form()
    {
        $this->checkbox('warehouse_id', 'Chọn Kho')->options(Warehouse::whereIsActive(1)->get()->pluck('name', 'id'))->default(2);
    }

    public function html()
    {
        return "<a class='create-order btn btn-sm btn-danger'><i class='fa fa-plus'></i> ".$this->name."</a>";
    }

}