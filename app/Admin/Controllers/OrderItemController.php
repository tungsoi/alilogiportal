<?php

namespace App\Admin\Controllers;

use App\Models\Alilogi\Order;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\OrderItem;
use Encore\Admin\Facades\Admin;

class OrderItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Danh sách sản phẩm';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OrderItem);
        $grid->model()->orderBy('created_at', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->where(function ($query) {
                $orders = Order::where('order_number', 'like', "%{$this->input}%")->get()->pluck('id');

                $query->whereIn('order_id', $orders);
            
            }, 'Mã đơn hàng');
        });

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order()->order_number('Mã đơn hàng mua hộ')->style('width: 100px');
        $grid->column('customer_name', 'Mã khách hàng')->display(function () {
            return $this->order->customer->name ?? "";
        });
        $grid->product_name('Tên SP')->style('width: 100px')->display(function () {
            return '<span class="ellipsis" data-toggle="tooltip" title="'.$this->product_name.'">'.mb_strimwidth($this->product_name, 0, 50, "...").'</span>' 
            . ' <br><a href="'.$this->product_link.'" target="_blank"> Link </a>';
        });
        $grid->column('product_image', 'Ảnh SP')->lightbox(['width' => 100, 'height' => 100]);
        $grid->product_size('Size')->display(function () {
            return $this->product_size != "null" ? $this->product_size : null;
        });
        $grid->product_color('Màu')->style('width: 100px');
        $grid->qty('Số lượng');
        $grid->qty_reality('Số lượng thực đặt');
        $grid->price('Giá (Tệ)');
        $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
            return $this->purchase_cn_transport_fee ?? 0;
        });
        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = $this->qty_reality * $this->price + $this->purchase_cn_transport_fee ;
            return number_format($totalPrice, 1) ?? 0; 
        });
        $grid->weight('KG');
        $grid->weight_date('Ngày vào KG');
        $grid->cn_transport_code('MVĐ');
        $grid->cn_order_number('MGD');
        $grid->customer_note('Khách hàng note')->style('width: 100px');
        $grid->admin_note('Admin note')->editable();
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });

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
        $show = new Show(OrderItem::findOrFail($id));

        $show->field('id', trans('admin.id'));
        $show->title(trans('admin.title'));
        $show->order(trans('admin.order'));
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
        $form = new Form(new OrderItem);

        $form->display('id', __('ID'));
        $form->text('title', trans('admin.title'))->rules('required');
        $form->number('order', trans('admin.order'))->default(0)->rules('required');
        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        return $form;
    }
}
