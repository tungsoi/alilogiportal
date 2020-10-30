<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Order\Deposite;
use App\Admin\Actions\Order\SuccessOrder;
use App\Models\Alilogi\Warehouse;
use App\Models\OrderItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\PurchaseOrder;
use App\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;

class CustomerOrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Danh sách đơn hàng';
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
        ->where('customer_id', Admin::user()->id)
        ->whereOrderType(1)->orderBy('created_at', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->like('order_number', 'Mã đơn hàng');
                $filter->equal('customer_id', 'Mã khách hàng')->select(User::whereIsCustomer(1)->get()->pluck('symbol_name', 'id'));
            });
            $filter->column(1/2, function ($filter) {
                $filter->equal('status', 'Trạng thái')->select(PurchaseOrder::STATUS);
            });
        });

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order_number('Mã đơn hàng')->label('success');
        $grid->customer_id('Mã khách hàng')->display(function () {
            return User::find($this->customer_id)->symbol_name ?? null;
        });
        $grid->column('total_items', 'Số SP')->display(function () {
            return $this->totalItems();
        });
        $grid->purchase_total_items_price('Tổng giá SP (Tệ)')->display(function () {
            return number_format($this->purchase_total_items_price);
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="label label-success">'.$amount.'</span>';
        });
        $grid->purchase_order_service_fee('Phí dịch vụ (VND)')->display(function () {
            return number_format($this->purchase_order_service_fee);
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="label label-success">'.$amount.'</span>';
        });

        $grid->purchase_order_transport_fee('Phí VCNĐ (VND)')->display(function () {
            return 0;
        });
        $grid->column('total_kg', 'Tổng KG')->display(function () {
            return $this->totalWeight();
        });
        $grid->column('price_weight', 'Giá KG (VND)')->display(function () {
            return number_format($this->price_weight);
        });
        $grid->warehouse()->name('Kho');
        $grid->deposited('Đã cọc (VND)')->display(function () {
            return number_format($this->deposited);
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
            $count = "";
            if ($this->status == PurchaseOrder::STATUS_ORDERED) {
                $count = "( ".$this->orderedItems() . " / " . $this->totalItems()." )";
            } else if ($this->status == PurchaseOrder::STATUS_IN_WAREHOUSE_VN) {
                $count = "( ".$this->warehouseVietnamItems() . " / " . $this->totalItems()." )";
            }

            $html = "<span class='label label-".PurchaseOrder::LABEL[$this->status]."'>".PurchaseOrder::STATUS[$this->status]." " .$count. "</span>";

            return $html;
        });
        $grid->admin_note('Admin ghi chú');
        $grid->current_rate('Tỷ giá (VND)')->display(function () {
            return number_format($this->current_rate);
        });
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $grid->column('staff', 'Nhân viên phụ trách')->display(function () {
            $html = "<ul style='padding-left: 15px;'>";
            $html .= '<li>Đặt hàng: ' . ($this->supporterOrder->name ?? "...") . "</li>";
            $html .= '<li>CSKH: ' . ($this->supporter->name ?? "...") . "</li>";
            $html .= '<li>Kho: ' . ($this->supporterWarehouse->name ?? "...") . "</li>";
            $html .= "</ul>";

            return $html;
        });
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableBatchActions();
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

        $grid->header(function ($query) use ($id) {
            $order = PurchaseOrder::find($id);

            $items = $order->items;
            $qty = $qty_reality = 0;
            foreach ($items as $item) {
                $qty_reality += $item->qty_reality;
                $qty += $item->qty;
            }
            $headers = ['Thông tin', 'Giá trị', ''];
            $rows = [
                ['Tổng giá trị đơn hàng', number_format($order->purchase_total_items_price), 'Kho', $order->warehouse->name ?? "Đang cập nhật"],
                ['Tổng tiền thực đặt', number_format($order->purchase_total_items_price), 'Nhân viên đặt hàng', $order->supporterOrder->name ?? "Đang cập nhật"],
                ['Tổng phí ship nội địa TQ', number_format($order->transport_fee), 'Nhân viên CSKH', $order->supporter->name ?? "Đang cập nhật"],
                ['Tổng số lượng', $qty, 'Nhân viên Kho', $order->supporterWarehouse->name ?? "Đang cập nhật"],
                ['Tổng thực đặt', $qty_reality],  
                ['Ngày tạo:', date('H:i | d-m-Y', strtotime($order->created_at))],
            ];

            $table = new Table($headers, $rows);

            return $table->render();
        });

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order()->order_number('MĐH')->help('Mã đơn hàng mua hộ')->label('success');
        $grid->column('customer_name', 'Mã KH')->display(function () {
            return $this->customer->symbol_name ?? "";
        })->help('Mã khách hàng');
        $grid->status('Trạng thái')->display(function () {
            $html = "<span class='label label-".OrderItem::LABEL[$this->status]."'>".OrderItem::STATUS[$this->status]."</span>";
            $html .= "<br> <br>";
            $html .= '<b><a href="'.$this->product_link.'" target="_blank"> Link sản phẩm </a></b>';
            return $html;
        });
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 50, 'height' => 50]);
        $grid->product_size('Kích thước')->display(function () {
            return $this->product_size != "null" ? $this->product_size : null;
        })->editable();
        $grid->product_color('Màu')->editable();
        $grid->qty('Số lượng');
        $grid->qty_reality('Số lượng thực đặt');
        $grid->price('Giá (Tệ)')->editable();
        $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
            return $this->purchase_cn_transport_fee ?? 0;
        })->editable()->help('Phí vận chuyển nội địa Trung quốc');
        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = $this->qty_reality * $this->price + $this->purchase_cn_transport_fee ;
            return number_format($totalPrice, 1) ?? 0; 
        });
        $grid->weight('Cân nặng (KG)');
        $grid->weight_date('Ngày vào KG');
        $grid->cn_transport_code('Mã vận đơn Alilogi')->editable();
        $grid->cn_order_number('Mã giao dịch')->editable();
        $grid->customer_note('Khách hàng ghi chú')->style('width: 100px')->editable();
        $grid->admin_note('Admin ghi chú')->editable();

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

        $form->divider("Thông tin");
        $form->display('order_number', 'Mã đơn hàng');
        $form->display('customer_name', 'Mã khách hàng');
        $form->select('warehouse_id', 'Kho')->options(Warehouse::all()->pluck('name', 'id'));
        $form->display('created_at', trans('admin.created_at'));
        $form->select('status', 'Trạng thái')->options(PurchaseOrder::STATUS);

        $form->divider("Các khoản chi phí");
        $form->currency('purchase_total_items_price', 'Tổng giá sản phẩm (Tệ)')->symbol('￥')->readonly()->width(200);
        $form->currency('current_rate', 'Tỷ giá chuyển đổi (VND)')->symbol('VND')->readonly()->width(200);

        $form->currency('purchase_order_service_fee', 'Phí dịch vụ (VND)')->symbol('VND')->width(200);
        
        $form->currency('purchase_order_transport_fee', 'Phí VCNĐ (VND)')->symbol('VND')->width(200);
        $form->currency('price_weight', 'Giá cân nặng (VND)')->symbol('VND')->width(200);
        $form->currency('final_total_price', 'Tổng giá cuối (VND)')->symbol('VND')->width(200)->disable();
        $form->currency('deposit_default', 'Số tiền phải cọc (70%) (VND)')->readonly()->symbol('VND')->width(200);
        $form->currency('deposited', 'Số tiền đã cọc (VND)')->symbol('VND')->width(200)->readonly();
        $form->text('deposited_at', 'Ngày vào cọc')->readonly();

        $form->divider("Nhân viên phụ trách");
        $form->select('supporter_order_id', 'Nhân viên đặt hàng')->options(User::whereIsCustomer(0)->get()->pluck('name', 'id'));
        $form->select('supporter_id', 'Nhân viên CSKH')->options(User::whereIsCustomer(0)->get()->pluck('name', 'id'));
        $form->select('support_warehouse_id', 'Nhân viên kho')->options(User::whereIsCustomer(0)->get()->pluck('name', 'id'));
        $form->hidden('deposited_at');

        $form->divider("Ghi chú");
        $form->textarea('admin_note', 'Admin ghi chú');
        $form->textarea('internal_note', 'Ghi chú nội bộ');
        $form->divider();
        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        $form->saving(function (Form $form) {
            $deposited = $form->deposited;
            if ($deposited != null) {
                $form->status = PurchaseOrder::STATUS_DEPOSITED_ORDERING;
                $form->deposited_at = date('Y-m-d H:i:s', strtotime(now()));
            }

            $order = PurchaseOrder::find($form->model()->id);
            $purchase_total_items_price = $order->purchase_total_items_price;
            $current_rate = $order->current_rate;
            $purchase_order_service_fee = $form->purchase_order_service_fee;
            $purchase_order_transport_fee = $form->purchase_order_transport_fee;

            $final_total_price = ($purchase_total_items_price *  $current_rate) + $purchase_order_service_fee + $purchase_order_transport_fee;
            $order->final_total_price = $final_total_price;
            $order->save();
        });

        return $form;
    }
}
