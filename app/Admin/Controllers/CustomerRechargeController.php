<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Alilogi\TransportRecharge;
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
        ->description('Chi tiết đơn hàng')

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

        $headers = ['STT', 'Ngày giao dịch', 'Nội dung giao dịch', 'Loại giao dịch', 'Số dư đầu kỳ (VND)', 'Trừ tiền (VND)', 'Nạp tiền (VND)', 'Số dư cuối kỳ (VND)'];

        $raw = [
            'order' =>  '',
            'payment_date'  =>  '',
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
}
