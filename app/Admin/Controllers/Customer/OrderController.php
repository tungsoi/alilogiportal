<?php

namespace App\Admin\Controllers\Customer;

use App\Admin\Actions\Customer\CancleOrder;
use App\Admin\Actions\Customer\CreateOrderFromCart;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use Encore\Admin\Facades\Admin;

class OrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Đơn hàng mua hộ';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PurchaseOrder());
        $grid->model()
        ->whereCustomerId(Admin::user()->id)
        ->whereOrderType(2)
        ->orderBy('created_at', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->like('order_number', 'Mã đơn hàng');
        });

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order_number('Mã đơn hàng');
        $grid->customer()->symbol_name('Mã khách hàng');

        $grid->purchase_total_items_price('Tổng giá SP (VND)')->display(function () {
            return number_format($this->purchase_total_items_price);
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="label label-success">'.$amount.'</span>';
        });
        $grid->purchase_service_fee('Phí dịch vụ (VND)')->display(function () {
            return number_format($this->purchase_service_fee);
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="label label-success">'.$amount.'</span>';
        });

        $grid->transport_fee('Phí VCNĐ (VND)')->display(function () {
            return 0;
        });
        $grid->column('total_kg', 'Tổng KG');
        $grid->column('price_kg', 'Giá KG (VND)');
        $grid->warehouse()->name('Kho');
        $grid->deposited('Đã đặt cọc (VND)')->display(function () {
            return $this->status != PurchaseOrder::STATUS_SUCCESS
                    ? number_format($this->deposited)
                    : number_format($this->deposit_default);
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="label label-success">'.$amount.'</span>';
        });
        $grid->deposited_at('Ngày cọc')->display(function () {
            return $this->deposited_at != null 
                ? date('d-m-Y', strtotime($this->deposited_at))
                : "";
        });
        $grid->final_total_price('Tổng giá cuối (VND)')->display(function () {
            return number_format($this->final_total_price);
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="label label-success">'.$amount.'</span>';
        });
        $grid->status('Trạng thái')->display(function () {
            return PurchaseOrder::STATUS[$this->status];
        });
        $grid->admin_note('Admin note');
        $grid->current_rate('Tỷ giá (VND)')->display(function () {
            return number_format($this->current_rate);
        });
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
            $actions->add(new CancleOrder($this->row->id));
        });

        Admin::script(
            <<<EOT

            $('tfoot').each(function () {
                $(this).insertAfter($(this).siblings('thead'));
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
        $form->hidden('status')->default(OrderItem::STATUS_PURCHASE_ITEM_NOT_ORDER);

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        return $form;
    }
}
