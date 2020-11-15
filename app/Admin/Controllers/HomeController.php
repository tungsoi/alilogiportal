<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\TransportOrderItem;
use App\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Callout;
use Encore\Admin\Widgets\InfoBox;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
        ->header('Bảng điều khiển')
        ->description(' ')
        ->row(function (Row $row) {
            if (Admin::user()->isRole('administrator')) {
                $row->column(4, new InfoBox('Quản trị viên', 'users', 'aqua', 'admin/auth/users', User::where('is_customer', 0)->count()));
                $row->column(4, new InfoBox('Khách hàng', 'book', 'green', '/admin/customers', User::where('is_customer', 1)->count()));
                $row->column(4, new InfoBox('Đơn hàng mua hộ', 'tag', 'yellow', '/admin/puchase_orders', PurchaseOrder::count()));
            } 
            else if (Admin::user()->isRole('customer_order')) {
                $row->column(3, new InfoBox('Số dư ví', '', 'aqua', 'admin/customer_recharges', number_format(Admin::user()->wallet)  . " VND"));
                $row->column(3, new InfoBox('Số đơn hàng', '', 'green', 'admin/customer_orders', PurchaseOrder::whereCustomerId(Admin::user()->id)->count()));
                $row->column(3, new InfoBox('Số sản phẩm', '', 'orange', 'admin/customer_items', PurchaseOrder::totalItemsByCustomerId(Admin::user()->id)));
                // $row->column(12, function (Column $column) {
                //     $column->append($this->grid()->render());
                // });
            }
            else {
                
            }
        });
    }
}
