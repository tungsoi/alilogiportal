<?php

namespace App\Admin\Actions\Order;

use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SuccessOrder extends BatchAction
{
    protected $selector = '.success-order';

    public $name = 'Bạn có đồng ý xác nhận đơn hàng này đã Hoàn thành ?';

    public function handle(Collection $collection, Request $request)
    {
        foreach ($collection as $model) {

            if ($model->status != PurchaseOrder::STATUS_IN_WAREHOUSE_VN) {
                return $this->response()->error("Đơn hàng ".$model->order_number." không ở trạng thái Đã về kho Việt Nam. Không thể xác nhận Hoàn thành. Vui lòng kiểm tra lại !")->refresh();
            }

            if ($model->warehouseVietnamItems() != $model->totalItems()) {
                return $this->response()->error("Các sản phẩm của Đơn hàng ".$model->order_number." chưa về hết Kho Việt Nam. Không thể xác nhận Hoàn thành. Vui lòng kiểm tra lại !")->refresh();
            }
        }

        foreach ($collection as $model) {
            PurchaseOrder::find($model->id)->update([
                'status'    =>  PurchaseOrder::STATUS_SUCCESS
            ]);
        }

        return $this->response()->success('Xác nhận hoàn thành đơn hàng thành công')->refresh();
    }

    public function form()
    {
        $this->text('note', 'Lưu ý')->default('Sau khi xác nhận hoàn thành sẽ không thể sửa lại. Vui lòng kiểm tra trước khi thực hiện.')->disable();
    }

    public function html()
    {
        return "<a class='success-order btn btn-sm btn-success'><i class='fa fa-cart-plus'></i> &nbsp; Xác nhận đơn hàng thành công</a>";
    }

}