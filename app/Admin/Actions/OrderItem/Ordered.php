<?php

namespace App\Admin\Actions\OrderItem;

use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class Ordered extends BatchAction
{
    protected $selector = '.ordered';

    public $name = 'Bạn có đồng ý chuyển trạng thái của các sản phẩm đã chọn thành "Đã đặt hàng" ?';


    // Chuyển trạng thái từ chưa được đặt hàng -> đã đặt hàng
    public function handle(Collection $collection, Request $request)
    {
        $order_id = 0;
        $orders = [];
        foreach ($collection as $model) {
            $item_code = "SPMH-".str_pad($model->id, 5, 0, STR_PAD_LEFT);
            if ($model->status == OrderItem::STATUS_PURCHASE_ITEM_ORDERED) {
                // nếu trạng thái của sản phẩm là đã đặt hàng
                return $this->response()->error("Sản phẩm ".$item_code." đã được đặt hàng. Không thể xác nhận Đã đặt hàng. Vui lòng kiểm tra lại !")->refresh();
            } else if ($model->status == OrderItem::STATUS_PURCHASE_WAREHOUSE_VN) {
                // nếu trạng thái của sản phẩm là đã về kho Việt Nam
                return $this->response()->error("Sản phẩm ".$item_code." đã về kho Việt Nam. Không thể xác nhận Đã đặt hàng. Vui lòng kiểm tra lại !")->refresh();
            } else if ($model->status == OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                // nếu trạng thái của sản phẩm là đã về kho Việt Nam
                return $this->response()->error("Sản phẩm ".$item_code." đã hết hàng. Không thể xác nhận Đã đặt hàng. Vui lòng kiểm tra lại !")->refresh();
            } 

            $orders[] = $model->order_id;
        }

        foreach ($orders as $order_id) {
            $order = PurchaseOrder::find($order_id);
            if ($order->status != PurchaseOrder::STATUS_DEPOSITED_ORDERING) {
                return $this->response()->error("Đơn hàng " .$order->order_number. " chưa được vào tiền cọc. Vui lòng đặt cọc cho đơn hàng này trước khi đặt hàng các sản phẩm." )->refresh();
            }
        }

        // chỉ còn các sản phẩm ở trạng thái chưa đặt hàng
        // update -> đã đặt hàng
        foreach ($collection as $model) {
            OrderItem::find($model->id)->update([
                'status'    =>  OrderItem::STATUS_PURCHASE_ITEM_ORDERED
            ]);
            $order_id = $model->order_id;
        }

        $order = PurchaseOrder::find($order_id);

        $flag_all_item_ordered = true;
        foreach ($order->items as $item) {
            if ($item->status != OrderItem::STATUS_PURCHASE_ITEM_ORDERED) { // trạng thái của sản phẩm != đã được đặt hàng
                $flag_all_item_ordered = false;
            }
        }

        // if ($flag_all_item_ordered) {
        //     // update trạng thái của đơn hàng -> đã đặt hàng
        //     PurchaseOrder::find($order_id)->update([
        //         'status'    =>  PurchaseOrder::STATUS_ORDERED
        //     ]);
        // }
       
        return $this->response()->success('Xác nhận đặt hàng thành công')->refresh();
    }

    public function form()
    {
        $this->text('note', 'Lưu ý')->default('Sau khi chuyển trạng thái sẽ không thể sửa lại. Vui lòng kiểm tra trước khi thực hiện.')->disable();
    }

    public function html()
    {
        return "<a class='ordered btn btn-sm btn-primary' data-toggle='tooltip' title='Xác nhận đã đặt hàng các sản phẩm được chọn'><i class='fa fa-cart-plus'></i> &nbsp; Đã đặt hàng sản phẩm</a>";
    }

}