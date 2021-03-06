<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\OrderItem\Ordered;
use App\Admin\Actions\OrderItem\WarehouseVietnam;
use App\Models\Alilogi\TransportOrderItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use App\User;
use Encore\Admin\Facades\Admin;

class CustomerItemController extends AdminController
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
        $grid->model()
        ->where('customer_id', Admin::user()->id)
        ->whereNotNull('order_id')
        ->where('status', '!=', OrderItem::PRODUCT_NOT_IN_CART)
        ->orderBy('created_at', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $orders = PurchaseOrder::where('order_number', 'like', "%{$this->input}%")->get()->pluck('id');
    
                    $query->whereIn('order_id', $orders);
                
                }, 'Mã đơn hàng');

                $filter->where(function ($query) {
                    switch ($this->input) {
                        case 'yes':
                            // custom complex query if the 'yes' option is selected
                            $query->whereNotNull('cn_code');
                            break;
                        case 'no':
                            $query->whereNull('cn_code');
                            break;
                    }
                }, 'Tình trạng sản phẩm')->radio([
                    '' => 'Tất cả',
                    'yes' => 'Đã có mã vận đơn',
                    'no' => 'Chưa có mã vận đơn',
                ]);
                
            });
            $filter->column(1/2, function ($filter) {
                $filter->like('cn_code', 'Mã vận đơn');
            });
            
        });
        // $grid->fixColumns(6);
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->column('info', 'Mã đơn hàng')->display(function () {
            $html = "";
            $html .= $this->order->order_number ?? "";
            $html .= "<br><span class='label label-".OrderItem::LABEL[$this->status]."'>".OrderItem::STATUS[$this->status]."</span>";
            $html .= "<br> <br>";
            $html .= '<b><a href="'.$this->product_link.'" target="_blank"> Link sản phẩm </a></b>';
            $html .= '<br>'.date('H:i | d-m-Y', strtotime($this->created_at));
            return $html;
        })->width(150);
        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 50, 'height' => 50])->width(150);
        $grid->product_size('Size')->display(function () {
            return $this->product_size != "null" ? $this->product_size : null;
        })->width(150);
        $grid->product_color('Màu')->width(150);
        $grid->qty('Số lượng');
        $grid->qty_reality('Thực đặt');
        $grid->price('Giá (Tệ)');
        $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
            return $this->purchase_cn_transport_fee ?? 0;
        });
        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = ($this->qty_reality * $this->price) + $this->purchase_cn_transport_fee ;
            return number_format($totalPrice, 2) ?? 0; 
        });
        $grid->weight('Cân nặng (KG)')->display(function () {
            
            if ($this->weight != null) {
                $html = "<p>".$this->weight."</p>";
                $html .= "<p>" . $this->weight_date != null ? date('Y-m-d', strtotime($this->weight_date)) : null."</p>";

                return $html;
            }
            
            return null;
        });
        $grid->cn_code('Mã vận đơn Alilogi');
        $grid->customer_note('Ghi chú')->width(100)->editable();
        $grid->admin_note('Admin ghi chú');

        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disablePagination();
        
        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->paginate(1000);

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

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderItem);

        $form->text('cn_code', 'Mã vận đơn');
        $form->text('cn_order_number', 'Mã giao dịch');
        $form->text('admin_note', 'Admin ghi chú');

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        $form->saving(function (Form $form) {
            $cn_code = $form->cn_code;
            $transport_item = TransportOrderItem::select('cn_code', 'kg', 'warehouse_cn_date')->where('cn_code', $cn_code)->first();
            if ($transport_item) {
                OrderItem::find($form->model()->id)->update([
                    'weight'    =>  $transport_item->kg,
                    'weight_date'   =>  $transport_item->warehouse_cn_date
                ]);
            } 
            
        });

        return $form;
    }
}
