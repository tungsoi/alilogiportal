<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TransportOrderItem;
use App\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Callout;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Grid;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
        ->header('Bảng điều khiển')
        ->description('...')
        ->row(function (Row $row) {
            $words = 'Hệ thống đang trong quá trình triển khai và nâng cấp tính năng, mọi yêu cầu hoặc đóng góp ý kiến vui lòng gửi về địa chỉ hòm thư: thanhtung.atptit@gmail.com<br>Số điện thoại IT support: 0345.513.889';
            $row->column(12, function (Column $column) use ($words) {
                $column->append((new Callout($words))->style('success'));
            });
        })
        ->row(function (Row $row) {
            if (Admin::user()->isRole('administrator')) {
                $row->column(3, new InfoBox('Quản trị viên', 'users', 'aqua', 'admin/auth/users', User::where('is_customer', 0)->count()));
                $row->column(3, new InfoBox('Khách hàng', 'book', 'green', '/admin/customers', User::where('is_customer', 1)->count()));
                $row->column(3, new InfoBox('Mã vận đơn', 'tag', 'yellow', '/admin/transport_order_items', 2));
                $row->column(3, new InfoBox('Đơn hàng', 'file', 'red', '/admin/transport_orders', 3));
            } else {
                $row->column(12, function (Column $column) {
                    $column->append($this->grid()->render());
                });
            }
        });
    }
}
