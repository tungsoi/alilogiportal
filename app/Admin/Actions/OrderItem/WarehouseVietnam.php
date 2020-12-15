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
use Illuminate\Support\Facades\DB;

class WarehouseVietnam extends BatchAction
{
    protected $selector = '.warehouse-vietnam';

    public $name = 'Bạn có đồng ý chuyển trạng thái của các sản phẩm đã chọn thành "Đã về kho Việt Nam" ?';

    // chuyển trạng thái sản phẩm từ đã đặt hàng --> đã về kho việt nam
    public function handle(Collection $collection, Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->ajax()) {
                $order_id = 0;
                $ids = []; // list tất cả id
                $orders = []; // list tất cả các order id đã được chọn

                $items = [];

                // validate
                foreach ($collection as $model) {
                    if ($model->status != OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                        $items[] = $model->id;
                        $item_code = "SPMH-".str_pad($model->id, 5, 0, STR_PAD_LEFT);
                        if ($model->status == OrderItem::STATUS_PURCHASE_ITEM_NOT_ORDER) { // chưa đặt hàng
                            // chưa được đặt hàng
                            return $this->response()->error('Sản phẩm '.$item_code . ' chưa được đặt hàng. Không thể xác nhận Đã về kho Việt Nam. Vui lòng kiểm tra lại !"')->refresh();
                        } elseif ($model->status == OrderItem::STATUS_PURCHASE_WAREHOUSE_VN) { // về kho việt nam
                            // nếu trạng thái của sản phẩm là đã về kho Việt Nam
                            return $this->response()->error("Sản phẩm ".$item_code." đã về kho Việt Nam. Không thể xác nhận Đã về kho Việt Nam. Vui lòng kiểm tra lại !")->refresh();
                        }
                    }
                }

                // duyệt từng sản phẩm hợp lệ
                foreach ($items as $item_id) {
                    $item = OrderItem::find($item_id);
                    $item->status = OrderItem::STATUS_PURCHASE_WAREHOUSE_VN;
                    $item->save();

                    $order = PurchaseOrder::find($item->order_id);
                    $order->status = PurchaseOrder::STATUS_IN_WAREHOUSE_VN;
                    $order->save();
                }

                // check só lượng sản phẩm trong đơn đã về hết chưa.
                // nếu đã về hết thì chuyển trạng thái thành công.

                foreach ($items as $item_id) {
                    $order = PurchaseOrder::find($item->order_id);
                    if ($order->totalWarehouseVietnamItems() == $order->sumQtyRealityItem() && $order->status != PurchaseOrder::STATUS_SUCCESS) {
                        $order->status = PurchaseOrder::STATUS_SUCCESS;
                        $order->success_at = date('Y-m-d H:i:s', strtotime(now()));
                        $order->save();

                        if ($order->status == PurchaseOrder::STATUS_SUCCESS) {
                            $deposited = $order->deposited; // số tiền đã cọc
                            $total_final_price = round($order->totalBill() * $order->current_rate); // tổng tiền đơn hiện tại VND

                            $customer = $order->customer;
                            $wallet = $customer->wallet;

                            $flag = false;
                            if ($deposited <= $total_final_price) {
                                # Đã cọc < tổng đơn -> còn lại : tổng đơn - đã cọc
                                # -> trừ tiền của khách số còn lại

                                $owed = $total_final_price - $deposited;
                                $customer->wallet = $wallet - $owed;
                                $customer->save();
                                $flag = true;

                                if ($flag) {
                                    TransportRecharge::firstOrCreate([
                                        'customer_id'       =>  $order->customer_id,
                                        'user_id_created'   =>  1,
                                        'money'             =>  $owed,
                                        'type_recharge'     =>  TransportRecharge::DEDUCTION,
                                        'content'           =>  'Thanh toán đơn hàng mua hộ. Mã đơn hàng '.$order->order_number,
                                        'order_type'        =>  TransportRecharge::TYPE_ORDER
                                    ]);
                                }
                                DB::commit();
                                return $this->response()->success('Xác nhận các sản phẩm đã về kho Việt Nam thành công !')->refresh();
                            } else {

                                # Đã cọc > tổng đơn
                                # -> còn lại: đã cọc - tổng đơn
                                # -> cộng lại trả khách

                                $owed = $deposited - $total_final_price;
                                $customer->wallet = $wallet + $owed;
                                $customer->save();
                                $flag = true;

                                if ($flag) {
                                    TransportRecharge::firstOrCreate([
                                        'customer_id'       =>  $order->customer_id,
                                        'user_id_created'   =>  1,
                                        'money'             =>  $owed,
                                        'type_recharge'     =>  TransportRecharge::REFUND,
                                        'content'           =>  'Thanh toán đơn hàng mua hộ. Mã đơn hàng '.$order->order_number,
                                        'order_type'        =>  TransportRecharge::TYPE_ORDER
                                    ]);
                                }
        
                                DB::commit();
                                return $this->response()->success('Xác nhận các sản phẩm đã về kho Việt Nam thành công !')->refresh();
                            }
                        }
                    }
                }

                DB::commit();
                return $this->response()->success('Xác nhận các sản phẩm đã về kho Việt Nam thành công !')->refresh();
            }
        }
        catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            return $this->response()->error($e->getMessage())->refresh();
        }
    }

    public function form()
    {
        $this->textarea('note', 'Lưu ý')->default('Sau khi chuyển trạng thái sẽ không thể sửa lại. Vui lòng kiểm tra trước khi thực hiện. Đơn hàng sẽ tự động thanh toán khi tất cả sản phẩm trong đơn đã về Việt Nam.')->disable();
    }

    public function html()
    {
        return "<a class='warehouse-vietnam btn btn-sm btn-success' data-toggle='tooltip' title='Xác nhận các sản phẩm được chọn đã về kho Việt Nam'><i class='fa fa-cart-plus'></i> &nbsp; Đã về kho Việt Nam</a>";
    }

}