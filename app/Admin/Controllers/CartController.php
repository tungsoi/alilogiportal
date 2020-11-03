<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Customer\CreateOrderFromCart;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\OrderItem;
use Encore\Admin\Facades\Admin;
use App\User;

class CartController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = trans('Giỏ hàng');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OrderItem);
        $grid->model()
        ->whereNull('order_id')
        ->whereCustomerId(Admin::user()->id)
        ->whereStatus(OrderItem::PRODUCT_NOT_IN_CART)
        ->orderBy('created_at', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
        });

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 100, 'height' => 100]);
        $grid->shop_name('Tên shop');
        $grid->product_name('Tên sản phẩm');
        $grid->product_link('Link sản phẩm')->link();
        $grid->product_size('Size');
        $grid->product_color('Màu');
        $grid->qty('Số lượng đặt');
        $grid->price('Giá (Tệ)');
        $grid->customer_note('Ghi chú');
        $grid->status('Trạng thái')->display(function () {
            return OrderItem::STATUS[$this->status];
        })->label('primary');
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new CreateOrderFromCart());
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
        
        $form->text('shop_name', 'Tên shop');
        $form->text('product_name', 'Tên sản phẩm');
        $form->text('product_link', 'Link sản phẩm')->rules('required');
        $form->image('product_image','Ảnh sản phẩm')->thumbnail('small', $width = 150, $height = 150)->rules('required');
        $form->text('product_size', 'Size sản phẩm')->rules('required');
        $form->text('product_color', 'Màu sắc sản phẩm')->rules('required');
        $form->number('qty', 'Số lượng')->rules('required');
        $form->currency('price', 'Giá sản phẩm (Tệ)')->rules('required')->symbol('￥');
        $form->textarea('customer_note', 'Ghi chú');
        $form->hidden('customer_id')->default(Admin::user()->id);
        $form->hidden('status')->default(OrderItem::PRODUCT_NOT_IN_CART);
        $form->hidden('qty_reality');

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        $form->saving(function (Form $form) {
            $form->qty_reality = $form->qty;
        });

        return $form;
    }
}
