<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Customer\CreateOrderFromCart;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\OrderItem;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

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
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->title($this->title())
            ->description('Danh sách sản phẩm trong giỏ')
            ->body($this->grid());
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
        $grid->header(function () {
            return '
                - Hướng dẫn: <br>
                - Để thêm sản phẩm bạn muốn mua, hãy nhấn chọn nút "Thêm sản phẩm vào giỏ hàng" <br>
                - Để tạo đơn hàng, vui lòng nhấn chọn từng sản phẩm, sau đó nhấn chọn nút "Tạo đơn hàng"
            ';
        });
        $grid->column('number', 'STT');
        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 100, 'height' => 100]);
        $grid->shop_name('Tên shop')->width(200);
        $grid->product_name('Tên sản phẩm')->width(200);
        $grid->product_link('Link sản phẩm')->display(function () {
            return "<a href=".$this->product_link." target='_blank'>Link sản phẩm</a>";
        })->width(150);
        $grid->product_size('Size')->width(150);
        $grid->product_color('Màu')->width(150);
        $grid->qty('Số lượng')->editable();
        $grid->price('Đơn giá (Tệ)')->editable();
        $grid->column('total', 'Thành tiền (Tệ)')->display(function () {
            return number_format($this->qty * $this->price, 2);
        });
        $grid->customer_note('Ghi chú')->editable()->width(200);

        $grid->disableColumnSelector();
        $grid->disableExport();
        $grid->disableFilter();

        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
            
            $actions->append('
            <a href="'.route('admin.carts.edit', $this->getKey()).'" class="btn btn-xs btn-info ">
                <i class="fa fa-edit"></i> Sửa
            </a>');
            $actions->append('
                <a class="btn btn-xs btn-danger btn-customer-delete-item" data-id="'.$this->getKey().'">
                    <i class="fa fa-trash"></i><span class="hidden-xs">&nbsp; Xoá</span>
                </a>
            ');
        });
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append('
                <a href="'.route('admin.carts.create').'" class="btn btn-sm btn-success">
                    <i class="fa fa-plus"></i><span class="hidden-xs">&nbsp;&nbsp;Thêm sản phẩm vào giỏ</span>
                </a>
            ');
            $tools->append(new CreateOrderFromCart());
        });
        $grid->paginate(100);

        Admin::script(
            <<<EOT
            
            $(document).on('click', '.btn-customer-delete-item', function () {
                $.ajax({
                    type: 'POST',
                    url: '/api/customer-delete-item-from-cart',
                    data: {
                        id: $(this).data('id')
                    },
                    success: function(response) {
                        if (response.error == false) {
                            toastr.success('Xoá thành công.');

                            setTimeout(function () {
                                window.location.reload();
                            }, 500);
                            
                        } else {
                            alert('Xảy ra lỗi: ' + response.msg);
                        }
                    }
                });
            });
EOT
    );

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
            $form->textarea('customer_note', 'Ghi chú của bạn');
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
        $form->text('product_link', 'Link sản phẩm')->rules('required');
        $form->image('product_image','Ảnh sản phẩm')->thumbnail('small', $width = 150, $height = 150);
        $form->text('product_size', 'Size sản phẩm')->rules('required');
        $form->text('product_color', 'Màu sắc sản phẩm')->rules('required');
        $form->number('qty', 'Số lượng')->rules('required');
        $form->currency('price', 'Giá sản phẩm (Tệ)')->rules('required')->symbol('￥')->digits(2);
        $form->textarea('customer_note', 'Ghi chú của bạn');
        $form->hidden('customer_id')->default(Admin::user()->id);
        $form->hidden('status')->default(OrderItem::PRODUCT_NOT_IN_CART);
        $form->hidden('qty_reality');

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

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

    public function addCart1688($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->formEdit1688((string) $id)) ;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function formEdit1688($id = "")
    {
        $ids = explode(',', $id);
        $items = OrderItem::whereIn('id', $ids)->get();

        return view('admin.cart1688', compact('items'))->render();
    }

    public function storeAdd1688(Request $request)
    {
        # code...
        $data = $request->all();
        $customer_id = Admin::user()->id;
        foreach ($data['id'] as $item_id => $raw) {
            $res = [
                'shop_name' =>  $data['shop_name'][$item_id],
                'product_name' =>  $data['product_name'][$item_id],
                'product_link' =>  $data['product_link'][$item_id],
                'product_size' =>  $data['product_size'][$item_id],
                'product_color' =>  $data['product_color'][$item_id],
                'qty' =>  $data['qty'][$item_id],
                'price' =>  $data['price'][$item_id],
                'customer_note' =>  $data['customer_note'][$item_id],
                'qty_reality'   =>  $data['qty'][$item_id],
                'customer_id'   =>  Admin::user()->id,
                'status'  =>  OrderItem::PRODUCT_NOT_IN_CART
            ];

            OrderItem::find($item_id)->update($res);
        }

        admin_toastr('Lưu thành công', 'success');
        return redirect()->route('admin.carts.index');
    }
}
