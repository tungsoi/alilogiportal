<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Alilogi\TransportRecharge;
use App\User;

class TransactionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Tiền giao dịch khách hàng';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TransportRecharge);
        $grid->model()->orderBy('id', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->equal('customer_id', 'Mã khách hàng')->select(User::whereIsCustomer(1)->get()->pluck('symbol_name', 'id'));
            $filter->equal('type_recharge', 'Loại giao dịch')
            ->select(TransportRecharge::TRANSACTION);
        });

        $grid->customer_id('Mã khách hàng')->display(function () {
            return $this->customer->symbol_name;
        });
        $grid->user_id_created('Người tạo')->display(function () {
            return $this->userCreated->name;
        });
        $grid->money('Số tiền');
        $grid->type_recharge('Loại giao dịch')->display(function () {
            $content = TransportRecharge::ALL_RECHARGE[$this->type_recharge];
            $color = TransportRecharge::COLOR[$this->type_recharge];

            return "<span class='label label-".$color."'>".$content."</span>";
        });
        $grid->content('Nội dung giao dịch');
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();

            if ($this->row->type_recharge == TransportRecharge::DEDUCTION) {
                $actions->disableEdit();
            }
        });

        $grid->paginate(100);
        
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
        $show = new Show(TransportRecharge::findOrFail($id));

        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TransportRecharge);

        $form->display('id', __('ID'));
        $form->select('type_recharge', 'Loại giao dịch')->options(
            TransportRecharge::RECHARE_SEARCH
        );
        $form->display('created_at', trans('admin.created_at'));

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        return $form;
    }
}
