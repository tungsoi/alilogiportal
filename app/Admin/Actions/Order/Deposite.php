<?php

namespace App\Admin\Actions\Order;

use App\Models\Alilogi\TransportRecharge;
use App\User;
use App\Models\PurchaseOrder;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class Deposite extends RowAction
{
    public $name = "Đặt cọc";

    public function handle(Model $model, Request $request)
    {
        // $model ...
        $deposite = $request->get('deposite');
        $deposite = (int) str_replace(",", "", str_replace(".", "", $deposite));

        if ($deposite > (int) $model->final_total_price) {
            return $this->response()->error("Số tiền vào cọc lớn hơn tổng giá trị đơn hàng !")->refresh();
        }

        PurchaseOrder::find($model->id)->update([
            'deposited' =>  $deposite,
            'user_id_deposited' =>  $request->get('user_id_deposited'),
            'deposited_at'  =>  date('Y-m-d', strtotime(now())),
            'status'    =>  PurchaseOrder::STATUS_DEPOSITED_ORDERING
        ]);

        $alilogi_user = User::find($model->customer_id);
        $wallet = $alilogi_user->wallet;
        $alilogi_user->wallet = $wallet - $deposite;
        $alilogi_user->save();

        TransportRecharge::create([
            'customer_id'   =>  $model->customer_id,
            'user_id_created'   => $request->get('user_id_deposited'),
            'money' =>  $deposite,
            'type_recharge' =>  TransportRecharge::DEPOSITE_ORDER,
            'content'   =>  'Đặt cọc đơn hàng mua hộ. Mã đơn hàng '.$model->order_number,
            'order_type'    =>  TransportRecharge::TYPE_ORDER
        ]);

        return $this->response()->success('Vào cọc cho đơn hàng mua hộ mã '. $model->order_number. " thành công. Đã trừ tiền trong tài khoản của khách hàng ".$alilogi_user->symbol_name." !")->refresh();
    }

    public function form()
    {   
        $this->text('deposite', 'Số tiền đặt cọc')->rules(['required'])->help('Nhập số tiền dạng: 1,000,000 hoặc 1.000.000');
        $this->text('staff_deposited', 'Nhân viên thực hiện')->default(Admin::user()->name)->readonly();
        $this->hidden('user_id_deposited')->default(Admin::user()->id);
    }

}