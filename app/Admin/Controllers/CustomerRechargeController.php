<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Alilogi\TransportRecharge;
use App\User;
use Encore\Admin\Facades\Admin;

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
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $id = Admin::user()->id;
        $grid = new Grid(new TransportRecharge);
        $grid->model()
        ->where('money', ">", 0)
        ->where('customer_id', $id)->orderBy('id', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->equal('type_recharge', 'Loại giao dịch')->select(TransportRecharge::RECHARGE);
        });

        $grid->header(function ($query) use ($id) {
            $wallet = User::find($id)->wallet;
            $color = $wallet > 0 ? 'green' : 'red';
            return '<h4 style="font-weight: bold;">Số dư hiện tại: <span  style="color: '.$color.'">'. number_format($wallet) ."</span> (VND)</h4>";
        });        
        $grid->id('ID');
        $grid->order_type('Website')->display(function () {
            return $this->order_type == TransportRecharge::TYPE_ORDER ? "<span class='label label-primary'>Alooder</span>" : "<span class='label label-danger'>Alilogi</span>";
        });
        $grid->customer_id('Tên khách hàng')->display(function () {
            return $this->customer->name ?? "";
        });
        $grid->user_id_created('Nhân viên thực hiện')->display(function () {
            return $this->userCreated->name ?? "";
        });
        $grid->money('Số tiền')->display(function () {
            if ($this->money > 0) {
                return '<span class="label label-success">'.number_format($this->money) ?? "0".'</span>';
            }

            return '<span class="label label-danger">'.number_format($this->money).'</span>';
        });
        $grid->type_recharge('Loại giao dịch')->display(function () {
            if ($this->order_type == TransportRecharge::TYPE_TRANSPORT) {
                if ($this->type_recharge == TransportRecharge::PAYMENT) {
                    return '<span class="label label-'.TransportRecharge::COLOR[TransportRecharge::PAYMENT].' ">'.TransportRecharge::RECHARGE_PAYMENT.'</span>';
                }
                return '<span class="label label-'.TransportRecharge::COLOR[$this->type_recharge].' ">'.TransportRecharge::RECHARGE[$this->type_recharge].'</span>';
            } else {
                $label = "default";
                if ($this->type_recharge == TransportRecharge::DEPOSITE_ORDER ) {
                    $label = "warning";
                    $text = TransportRecharge::DEPOSITE_ORDER_TEXT;
                } else {
                    $label = "danger";
                    $text = TransportRecharge::PAYMENT_ORDER_TEXT;
                }

                return '<span class="label label-'.$label.'">'.$text.'</span>';
            }
        });
        $grid->content('Nội dung');
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $grid->disableActions();

        $grid->disableCreateButton();
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        //
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        // 
    }
}
