<?php

namespace App\Admin\Controllers;

use App\Models\Alilogi\Order;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Alilogi\TransportRecharge;
use App\Models\PurchaseOrder;
use App\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Table;

class CustomerRechargeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = "Lịch sử giao dịch ví tiền";
    }

     /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return $content->header('Lịch sử giao dịch ví')
        ->description('Chi tiết')

        ->row(function (Row $row) {
            $row->column(4, new InfoBox('Số dư ví', 'users', 'danger', '#', number_format(Admin::user()->wallet)));
        })
        ->row(function (Row $row)
        {
            // Tab thong tin chi tiet bao cao
            $row->column(12, function (Column $column) 
            {
                $column->append((new Box('Chi tiết các giao dịch', $this->grid())));
            });
        });
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $id = Admin::user()->id;
        $res = TransportRecharge::where('money', ">", 0)
        ->where('customer_id', $id)
        ->orderBy('id', 'desc')
        ->get();

        $headers = ['STT', 'Ngày giao dịch', 'Đơn hàng', 'Nội dung giao dịch', 'Loại giao dịch', 'Số dư đầu kỳ (VND)', 'Trừ tiền (VND)', 'Nạp tiền (VND)', 'Số dư cuối kỳ (VND)'];

        $raw = [
            'order' =>  '',
            'payment_date'  =>  '',
            'order_link'    =>  '',
            'type_recharge' =>  '',
            'content'   =>  '',
            'before_payment'    =>  '',
            'down'   =>  '',
            'up'    =>  '',
            'after_payment'
        ];
        $data = [];

        foreach ($res as $key => $record) {
            $type = $record->type_recharge;
            if (in_array($type, TransportRecharge::UP)) {
                $up = $record->money;
                $down = null;
                $flag = 'up';
            } else {
                $down = $record->money;
                $up = null;
                $flag = 'down';
            }

            $data[] = [
                'order' =>  $key + 1,
                'payment_date'  =>  date('H:i | d-m-Y', strtotime($record->created_at)),
                'order_link'    =>  $this->convertOrderLink($record->content, $record->type_recharge),
                'type_recharge' =>  TransportRecharge::ALL_RECHARGE[$record->type_recharge],
                'content'   =>  $record->content,
                'before_payment'    =>  '',
                'down'   =>  $down,
                'up'    => $up,
                'after_payment' =>  '',
                'flag'  =>  $flag
            ];
        }

        $data = array_reverse($data);
        foreach ($data as $key => $raw) {
            if ($key == 0) {
                $data[0]['before_payment']  = 0;
                if ($data[0]['flag'] == 'up') {
                    $data[0]['after_payment'] = $data[0]['before_payment'] + $data[0]['up'];
                }
                else {
                    $data[0]['after_payment'] = $data[0]['before_payment'] - $data[0]['down'];
                }
            }
            else {
                $data[$key]['before_payment']  = $data[$key-1]['after_payment'];
                if ($data[$key]['flag'] == 'up') {
                    $data[$key]['after_payment'] = $data[$key]['before_payment'] + $data[$key]['up'];
                }
                else {
                    $data[$key]['after_payment'] = $data[$key]['before_payment'] - $data[$key]['down'];
                }
            }
        }

        $data = array_reverse($data);

        foreach ($data as $key => $last_raw) {

            $data[$key]['before_payment'] = $data[$key]['before_payment'] >= 0 
            ? "<span class='label label-success'>".number_format($data[$key]['before_payment'])."</span>"
            : "<span class='label label-danger'>".number_format($data[$key]['before_payment'])."</span>";

            $data[$key]['down'] = $data[$key]['down'] != null
            ? "<span class='label label-danger'>".number_format($data[$key]['down'])."</span>"
            : null;
            
            $data[$key]['up'] = $data[$key]['up'] != null
            ? "<span class='label label-success'>".number_format($data[$key]['up'])."</span>"
            : null;
        
            $data[$key]['after_payment'] = $data[$key]['after_payment'] >= 0 
            ? "<span class='label label-success'>".number_format($data[$key]['after_payment'])."</span>"
            : "<span class='label label-danger'>".number_format($data[$key]['after_payment'])."</span>";

            unset($data[$key]['flag']);
        }

        $table = new Table($headers, $data);

        return $table->render();
    }
    
    public function convertOrderLink($content, $type)
    {
        # code...

        switch ($type) {
            case 4: 
                $subs = explode("Thanh toán đơn hàng", $content);
                $order_number = trim($subs[1]);
                $order = Order::whereOrderNumber($order_number)->first();
                if ($order) {
                    return "<a href='https://alilogi.vn/admin/transport_orders/".$order->id."' target='_blank'>Đơn hàng vận chuyển ".$subs[1]."</a>";
                }
                else {
                    return null;
                }
            case 5: 
                $subs = explode("Đặt cọc đơn hàng mua hộ. Mã đơn hàng", $content);
                $order_number = trim($subs[1]);
                $order = PurchaseOrder::whereOrderNumber($order_number)->first();

                if ($order) {
                    return "<a href='".route('admin.customer_orders.show', $order->id)."' target='_blank'>Đơn hàng order ".$subs[1]."</a>";
                }
                else {
                    return null;
                }
                
            
            case 6: 
                $subs = explode("Thanh toán đơn hàng mua hộ. Mã đơn hàng", $content);
                $order_number = trim($subs[1]);
                $order = PurchaseOrder::whereOrderNumber($order_number)->first();
                if ($order) {
                    return "<a href='".route('admin.customer_orders.show', $order->id)."' target='_blank'>Đơn hàng order ".$subs[1]."</a>";
                }
                else {
                    return null;
                }
                
            default:
                return null;
        }
        return $content;
    }
}
