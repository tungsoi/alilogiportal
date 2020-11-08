<?php

namespace App\Admin\Actions\Order;

use App\Models\Alilogi\TransportRecharge;
use App\User;
use App\Models\PurchaseOrder;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class ConfirmOrdered extends RowAction
{
    public $name = "Xác nhận đã đặt hàng";

    public function handle(Model $model, Request $request)
    {
       
    }

    public function form()
    {   
        //
    }

}