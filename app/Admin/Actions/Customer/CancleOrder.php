<?php

namespace App\Admin\Actions\Customer;

use App\Admin\Services\OrderService;
use App\Models\Alilogi\Warehouse;
use App\Models\ExchangeRate;
use App\Models\Item;
use App\Models\PurchaseOrder;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CancleOrder extends RowAction
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row').on('click', function () {

    // Your code.
    console.log($(this).data('id'));

});

SCRIPT;
    }

    public function render()
    {
        Admin::script($this->script());

        return '<a href="http://127.0.0.1:8000/admin/customers/orders/53">Huỷ</a>';
    }

    public function __toString()
    {
        return $this->render();
    }

}