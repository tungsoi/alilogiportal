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
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

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
        $grid->customer_note('Ghi chú')->editable();
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
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function addCart($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->formEdit((int) $id)) ;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderItem);
        
        if (session()->has('booking_product')) {
            $booking = session()->get('booking_product');

            $form->text('shop_name', 'Tên shop')->default($booking[0]['shop_name']);
            $form->text('product_name', 'Tên sản phẩm')->default($booking[0]['product_name']);
            $form->text('product_link', 'Link sản phẩm')->rules('required')->default($booking[0]['product_link']);
            $form->html('<img src="'.$booking[0]['product_image'].'" style="width: 150px;"/>');

            $form->text('product_size', 'Size sản phẩm')->rules('required')->default($booking[0]['product_size']);
            $form->text('product_color', 'Màu sắc sản phẩm')->rules('required')->default($booking[0]['product_color']);
            $form->number('qty', 'Số lượng')->rules('required')->default($booking[0]['qty']);
            $form->currency('price', 'Giá sản phẩm (Tệ)')->rules('required')->symbol('￥')->digits(2)->default($booking[0]['price']);
            $form->textarea('customer_note', 'Ghi chú');
            $form->hidden('customer_id')->default(Admin::user()->id);
            $form->hidden('status')->default(OrderItem::PRODUCT_NOT_IN_CART);
            $form->hidden('qty_reality');
            $form->hidden('product_image')->default($booking[0]['product_image']);
    
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
            $form->disableViewCheck();
    
            $form->saving(function (Form $form) {
                $form->qty_reality = $form->qty;
            });
    
            return $form;
        }
        
        $form->text('shop_name', 'Tên shop');
        $form->text('product_name', 'Tên sản phẩm');
        $form->text('product_link', 'Link sản phẩm')->rules('required')->width('200px');
        $form->image('product_image','Ảnh sản phẩm')->thumbnail('small', $width = 150, $height = 150);
        $form->text('product_size', 'Size sản phẩm')->rules('required');
        $form->text('product_color', 'Màu sắc sản phẩm')->rules('required');
        $form->number('qty', 'Số lượng')->rules('required');
        $form->currency('price', 'Giá sản phẩm (Tệ)')->rules('required')->symbol('￥')->digits(2);
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

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function formEdit($id = "")
    {
        $form = new Form(new OrderItem);

        $item = OrderItem::find($id);
        $form->setAction(route('admin.carts.storeAddByTool'));

        $form->text('shop_name', 'Tên shop')->default($item->shop_name);
        $form->text('product_name', 'Tên sản phẩm')->default($item->product_name);
        $form->text('product_link', 'Link sản phẩm')->rules('required')->default($item->product_link);
        $form->html('<img src="'.$item->product_image.'" style="width: 150px;"/>');

        $form->text('product_size', 'Size sản phẩm')->rules('required')->default($item->product_size);
        $form->text('product_color', 'Màu sắc sản phẩm')->rules('required')->default($item->product_color);
        $form->number('qty', 'Số lượng')->rules('required')->default($item->qty);
        $form->currency('price', 'Giá sản phẩm (Tệ)')->rules('required')->symbol('￥')->digits(2)->default($item->price);
        $form->textarea('customer_note', 'Ghi chú');
        // $form->hidden('customer_id')->default(Admin::user()->id);
        $form->hidden('status')->default(OrderItem::PRODUCT_NOT_IN_CART);
        $form->hidden('qty_reality');
        $form->hidden('product_image')->default($item->product_image);

        $form->hidden('xid','Id')->default($item->id);

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        return $form;
    }

    public function storeAddByTool(Request $request)
    {
        # code...

        $data = $request->all();
        $data['customer_id'] = Admin::user()->id;
        $data['qty_reality'] = $data['qty'];
        OrderItem::find($data['xid'])->update($data);

        admin_toastr('Lưu thành công !', 'success');
        return redirect()->route('admin.carts.index');
    }

}
