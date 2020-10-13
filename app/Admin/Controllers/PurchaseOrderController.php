<?php

namespace App\Admin\Controllers;

use App\Models\OrderItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\PurchaseOrder;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;

class PurchaseOrderController extends AdminController
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
        $grid->model()->orderBy('created_at', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->like('title', trans('admin.title'));
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
        $grid->admin_note('Admin note')->editable();
        $grid->internal_note('Nội bộ note')->editable();
        $grid->current_rate('Tỷ giá (VND)')->display(function () {
            return number_format($this->current_rate);
        });
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
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
        $grid = new Grid(new OrderItem());
        $grid->model()->whereOrderId($id)->orderBy('created_at', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
        });

        $grid->header(function ($query) {
            $headers = ['Thông tin', 'Giá trị', ''];
            $rows = [
                ['Tổng giá trị đơn hàng', '3,780.0 Tệ', 'Kho', 'HBT 6322'],
                ['Tổng tiền thực đặt', '3,780.0 Tệ', 'Nhân viên CSKH', 'Nguyễn Văn A'],
                ['Tổng phí ship nội địa TQ', '0.0 Tệ', 'Nhân viên Kho', 'Nguyễn Văn B'],
                ['Tổng số lượng', '9'],
                ['Tổng thực đặt', '9'],  
                ['Ngày tạo:', '2020-10-09 15:45:44'],
            ];

            $table = new Table($headers, $rows);

            return $table->render();
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
        $grid->admin_note('Admin note');
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $grid->disableActions();
        $grid->disableBatchActions();
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableFilter();
        $grid->disableColumnSelector();

        $grid->tools(function ($tools) {
            $tools->append('<a href="'.route('admin.puchase_orders.index').'" class="btn btn-sm btn-default" title="Danh sách"><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;Danh sách</span></a>');
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PurchaseOrder);

        $form->display('id', __('ID'));
        $form->text('title', trans('admin.title'))->rules('required');
        $form->number('PurchaseOrder', trans('admin.PurchaseOrder'))->default(0)->rules('required');
        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        return $form;
    }
}
