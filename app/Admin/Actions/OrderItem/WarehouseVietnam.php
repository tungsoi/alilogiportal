<?php

namespace App\Admin\Actions\OrderItem;

use App\Models\Alilogi\TransportRecharge;
use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use App\User;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class WarehouseVietnam extends BatchAction
{
    protected $selector = '.warehouse-vietnam';

    public $name = 'Bạn có đồng ý chuyển trạng thái của các sản phẩm đã chọn thành "Đã về kho Việt Nam" ?';

    // chuyển trạng thái sản phẩm từ đã đặt hàng --> đã về kho việt nam
    public function handle(Collection $collection, Request $request)
    {
        $order_id = 0;
        $ids = []; // list tất cả id
        $orders = []; // list tất cả các order id đã được chọn

        foreach ($collection as $model) {
            $ids[] = $model->id;
            if (! isset($orders[$model->order_id])) {
                $orders[$model->order_id] = $model->order_id;
            }

            $item_code = "SPMH-".str_pad($model->id, 5, 0, STR_PAD_LEFT);
            // check trạng thái của sản phẩm
            if ($model->status == OrderItem::STATUS_PURCHASE_ITEM_NOT_ORDER) {
                // chưa được đặt hàng
                return $this->response()->error('Sản phẩm '.$item_code . ' chưa được đặt hàng. Không thể xác nhận Đã về kho Việt Nam. Vui lòng kiểm tra lại !"')->refresh();
            } else if ($model->status == OrderItem::STATUS_PURCHASE_WAREHOUSE_VN) {
                // nếu trạng thái của sản phẩm là đã về kho Việt Nam
                return $this->response()->error("Sản phẩm ".$item_code." đã về kho Việt Nam. Không thể xác nhận Đã về kho Việt Nam. Vui lòng kiểm tra lại !")->refresh();
            } else if ($model->status == OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                // nếu trạng thái của sản phẩm là đã về kho Việt Nam
                return $this->response()->error("Sản phẩm ".$item_code." đã hết hàng. Không thể xác nhận Đã đặt hàng. Vui lòng kiểm tra lại !")->refresh();
            } 
        }

        foreach ($collection as $model) {
            OrderItem::find($model->id)->update([
                'status'    =>  OrderItem::STATUS_PURCHASE_WAREHOUSE_VN
            ]);

            PurchaseOrder::find($model->order_id)->update([
                'status'    =>  PurchaseOrder::STATUS_IN_WAREHOUSE_VN
            ]);
        }

        foreach ($orders as $order_id) {
            $order = PurchaseOrder::find($order_id);
            if ($order->warehouseVietnamItems() == $order->totalItems()) {
                $order->status = PurchaseOrder::STATUS_SUCCESS;
                $order->save();

                $deposited = $order->deposited; // da coc
                $total_final_price = $order->finalPriceVND(); // tong tien

                $owed = $total_final_price - $deposited; // con lai

                $customer = User::find($order->customer_id);
                $wallet = $customer->wallet;
                $customer->wallet = $wallet - $owed; 
                $customer->save();

                TransportRecharge::create([
                    'customer_id'       =>  $model->customer_id,
                    'user_id_created'   =>  Admin::user()->id,
                    'money'             =>  $owed > 0 ? $owed : -($owed),
                    'type_recharge'     =>  TransportRecharge::PAYMENT_ORDER,
                    'content'           =>  'Thanh toán đơn hàng mua hộ. Mã đơn hàng '.$order->order_number.". Số tiền " . number_format($owed),
                    'order_type'        =>  TransportRecharge::TYPE_ORDER
                ]);
            }
        }

        return $this->response()->success('Xác nhận các sản phẩm đã về kho Việt Nam thành công !')->refresh();
    }

    public function form()
    {
        $this->text('note', 'Lưu ý')->default('Sau khi chuyển trạng thái sẽ không thể sửa lại. Vui lòng kiểm tra trước khi thực hiện.')->disable();
    }

    public function html()
    {
        return "<a class='warehouse-vietnam btn btn-sm btn-success' data-toggle='tooltip' title='Xác nhận đã về Việt Nam các sản phẩm được chọn'><i class='fa fa-cart-plus'></i> &nbsp; Đã về kho Việt Nam</a>";
    }

}