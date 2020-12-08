<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\OrderItem\Ordered;
use App\Admin\Actions\OrderItem\WarehouseVietnam;
use App\Models\Alilogi\Order;
use App\Models\Alilogi\TransportOrderItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use App\User;
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
    public function grid()
    {
        $grid = new Grid(new OrderItem);
        $grid->model()
        ->where('status', '!=', OrderItem::PRODUCT_NOT_IN_CART)
        ->whereNotNull('order_id')
        ->orderBy('created_at', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $orders = PurchaseOrder::where('order_number', 'like', "%{$this->input}%")->get()->pluck('id');
    
                    $query->whereIn('order_id', $orders);
                
                }, 'Mã đơn hàng');
                $filter->equal('customer_id', 'Mã khách hàng')->select(User::whereIsCustomer(1)->get()->pluck('symbol_name', 'id'));

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
                $filter->like('cn_order_number', 'Mã giao dịch');
                $filter->equal('status', 'Trạng thái')->select(OrderItem::STATUS);
            });
            
        });
        // $grid->fixColumns(6);
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->column('info', 'Mã đơn hàng')->display(function () {
            $order = $this->order;

            $html = "";
            if ($order && $order->order_number) {
                $html = $order->order_number ?? "";
            }
            $html .= "<br>";
            $html .= $order->customer->symbol_name ?? "";
            $html .= "<br>";
            $html .= "<span class='label label-".OrderItem::LABEL[$this->status]."'>".OrderItem::STATUS[$this->status]."</span>";
            $html .= "<br>".'<b><a href="'.$this->product_link.'" target="_blank"> Link sản phẩm </a></b>';
            
            return $html;
        });
        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 50, 'height' => 50]);
        $grid->product_size('Kích thước')->display(function () {
            return $this->product_size != "null" ? $this->product_size : null;
        })->editable()->width(100);
        $grid->product_color('Màu')->editable()->width(100);
        $grid->qty('Số lượng')->editable()->width(100);
        $grid->qty_reality('Số lượng thực đặt')->editable();
        $grid->price('Giá (Tệ)');
        $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
            return $this->purchase_cn_transport_fee ?? 0;
        })->help('Phí vận chuyển nội địa Trung quốc')->editable();
        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = ($this->qty_reality * $this->price) + $this->purchase_cn_transport_fee ;
            return number_format($totalPrice) ?? 0; 
        })->help('= Số lượng thực đặt x Giá (Tệ) + Phí vận chuyển nội địa (Tệ)');
        $grid->weight('Cân nặng (KG)')->help('Cân nặng lấy từ Alilogi')->editable();
        $grid->weight_date('Ngày vào KG')->help('Ngày vào cân sản phẩm ở Alilogi')->display(function () {
            return $this->weight_date != null ? date('Y-m-d', strtotime($this->weight_date)) : null;
        })->editable('date');
        $grid->cn_code('Mã vận đơn Alilogi')->editable();
        $grid->cn_order_number('Mã giao dịch')->editable();
        $grid->customer_note('Khách hàng ghi chú')->style('width: 100px')->editable();
        $grid->admin_note('Admin ghi chú')->editable();

        $grid->disableCreateButton();
        
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new Ordered());
            $tools->append(new WarehouseVietnam());
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function ($actions) {
            $actions->disableView();
            // $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->paginate(50);

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

        $form->select('status', 'Trạng thái')->options(OrderItem::STATUS);
        $form->text('product_size', 'Kích thước');
        $form->text('product_color', 'Màu sắc');
        $form->text('qty', 'Số lượng');
        $form->text('qty_reality', 'Số lượng thực đặt');
        $form->currency('price', 'Giá (Tệ)')->symbol('￥')->readonly();
        $form->currency('purchase_cn_transport_fee', 'VCND TQ (Tệ)')->symbol('￥');
        $form->text('weight', 'Cân nặng (KG)');
        $form->date('weight_date', 'Ngày vào KG');

        $form->text('cn_code', 'Mã vận đơn');
        $form->text('cn_order_number', 'Mã giao dịch');
        $form->textarea('customer_note', 'Khách hàng ghi chú');
        $form->textarea('admin_note', 'Admin ghi chú');

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();
        $form->tools(function (Form\Tools $tools) {
            // $tools->disableDelete();
            $tools->disableView();
            // $tools->disableList();
        });

        $form->saving(function (Form $form) {
            $cn_code = $form->cn_code;
            $transport_item = TransportOrderItem::select('cn_code', 'kg', 'warehouse_cn_date')->where('cn_code', $cn_code)->first();
            if ($transport_item) {
                OrderItem::find($form->model()->id)->update([
                    'weight'        =>  $transport_item->kg,
                    'weight_date'   =>  $transport_item->warehouse_cn_date
                ]);
            } 

            // if ($form->qty_reality == 0) {
            //     // $status = OrderItem::STATUS_PURCHASE_OUT_OF_STOCK;
            //     // OrderItem::find($form->model()->id)->update([
            //     //     'status'    =>  $status
            //     // ]);
            // } else if ($form->qty_reality > 0) {
            //     $item =  OrderItem::find($form->model()->id);

            //     if ($item->qty_reality == 0) {
            //         $status = OrderItem::STATUS_PURCHASE_ITEM_ORDERED;
            //         OrderItem::find($form->model()->id)->update([
            //             'status'    =>  $status
            //         ]);
            //     }
            // }
            
        });

        return $form;
    }
}
