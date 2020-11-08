<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\OrderItem\Ordered;
use App\Admin\Actions\OrderItem\WarehouseVietnam;
use App\Models\Alilogi\TransportOrderItem;
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
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;
use Illuminate\Http\Request;

class DetailOrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = trans('admin.menu_titles.name');
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
        return redirect()->route('admin.puchase_orders.index');
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content->header('Đơn hàng mua hộ')
        ->description('Chi tiết đơn hàng')
        ->row(function (Row $row) use ($id)
        {
            // Tab thong tin chi tiet bao cao
            $row->column(12, function (Column $column) use ($id) 
            {
                $column->append((new Box('Thông tin đơn hàng', $this->detail($id))));
            });

            // Tab thong tin xu ly
            $row->column(12, function (Column $column) use ($id)
            {
                $column->append((new Box('Danh sách sản phẩm', $this->grid($id)->render())));
            });
        });
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['create'] ?? trans('admin.create'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid($id)
    {
        $grid = new Grid(new OrderItem());
        $grid->model()->where('order_id',$id);

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $orders = PurchaseOrder::where('order_number', 'like', "%{$this->input}%")->get()->pluck('id');
    
                    $query->whereIn('order_id', $orders);
                
                }, 'Mã đơn hàng');
                $filter->equal('customer_id', 'Mã khách hàng')->select(User::whereIsCustomer(1)->get()->pluck('symbol_name', 'id'));
            });
            $filter->column(1/2, function ($filter) {
                $filter->like('cn_code', 'Mã vận đơn');
                $filter->like('cn_order_number', 'Mã giao dịch');
            });
            
        });
        $grid->fixColumns(5);
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order()->order_number('Mã đơn hàng')->help('Mã đơn hàng mua hộ')->label('primary')->display(function () {
            $html = "<span class='label label-primary'>".$this->order->order_number."</span>";
            $customer = $this->order->customer->symbol_name ?? $this->order->customer->email;

            $html .= "<br> <br> <span class='label label-info'>".$customer."</span>" ?? "";

            return $html;
        });
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
        $grid->qty('Số lượng')->editable();
        $grid->qty_reality('Số lượng thực đặt')->editable();
        $grid->price('Giá (Tệ)');
        $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
            return $this->purchase_cn_transport_fee ?? 0;
        })->help('Phí vận chuyển nội địa Trung quốc')->editable();
        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = $this->qty_reality * $this->price + $this->purchase_cn_transport_fee ;
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

        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new Ordered());
            $tools->append(new WarehouseVietnam());
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();

            $actions->append('
                <a href="'.route('admin.order_items.edit', $this->getKey()).'" class="grid-row-edit btn btn-primary btn-xs" target="_blank">
                    <i class="fa fa-edit"></i> &nbsp;Chỉnh sửa
                </a>'
            );
            
        });
        $grid->paginate(200);
        Admin::style('.box {border-top:none;}');

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
        $order = PurchaseOrder::find($id);

        if (! isset($order->items)) {
            admin_error('Đơn hàng bạn vừa chọn xem chi tiết không tồn tại. Vui lòng kiểm tra lại.');
            return redirect()->route('admin.puchase_orders.index');
        }
        $items = $order->items;
        $qty = $qty_reality = 0;
        $purchase_cn_transport_fee = 0; // Tổng phí ship nội địa TQ
        $total_price_reality = 0; // Tổng tiền thực đặt = Tổng thực đặt * giá 
        foreach ($items as $item) {
            $qty_reality += $item->qty_reality;
            $qty += $item->qty;
            $purchase_cn_transport_fee += $item->purchase_cn_transport_fee;
            $total_price_reality += $item->qty_reality * $item->price;
        }

        $total_bill = ($total_price_reality + $purchase_cn_transport_fee + $order->purchase_order_service_fee);
        $current_rate = $order->current_rate;
        $headers = ['Thông tin', 'Giá trị', ''];
        $rows = [
            ['Tổng giá trị đơn hàng = Tổng tiền thực đặt + ship nội địa + dịch vụ', number_format($total_bill) . " (Tệ)" . " = " . number_format($total_bill * $current_rate) . " (VND)", 'Kho', $order->warehouse->name ?? "Đang cập nhật"],
            ['Tổng tiền thực đặt', number_format($total_price_reality) . " (Tệ)" . " = " . number_format($total_price_reality * $current_rate) . " (VND)", 'Nhân viên đặt hàng', $order->supporterOrder->name ?? "Đang cập nhật"],
            ['Tổng phí ship nội địa Trung Quốc', number_format($purchase_cn_transport_fee) . " (Tệ)"  . " = " . number_format($purchase_cn_transport_fee * $current_rate) . " (VND)", 'Nhân viên CSKH', $order->supporter->name ?? "Đang cập nhật"],
            ['Tổng phí dịch vụ', number_format($order->purchase_order_service_fee) . " (Tệ)" . " = " . number_format($order->purchase_order_service_fee * $current_rate) . " (VND)", 'Nhân viên Kho', $order->supporterWarehouse->name ?? "Đang cập nhật"],
            ['Tổng số lượng', $qty, '<h6><b>Mã đơn hàng</b></h6>', '<h6><b>'.$order->order_number.'</b></h6>'],
            ['Tổng thực đặt', $qty_reality, '<h6><b>Mã khách hàng</b></h6>', '<h6><b>'.$order->customer->symbol_name.'</b></h6>'],  
            ['Ngày tạo:', date('H:i | d-m-Y', strtotime($order->created_at)), 'Tỷ giá', number_format($current_rate) . " (VND)"],
        ];

        $table = new Table($headers, $rows);

        return $table->render();
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
        $form->number('order', trans('admin.order'))->default(0)->rules('required');
        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        return $form;
    }

    public function editable(Request $request)
    {
        # code...

        $data = $request->all();
        $id = $data['pk'];
        $column = $data['name'];
        $value = $data['value'];
        $res = [];
        $res[$column] = $value;

        $item = OrderItem::find($id);
        $qty_reality = $item->qty_reality;

        // qty_reality
        if ($column == 'qty_reality' && $value == 0) {
            $res['status'] = OrderItem::STATUS_PURCHASE_OUT_OF_STOCK;
        } else if ($data['name'] == 'qty_reality' && $data['value'] > 0) {
            if ($qty_reality == 0) {
                $res['status'] = OrderItem::STATUS_PURCHASE_ITEM_ORDERED;
            }
        }

        // cn_code alilogi
        if ($column == 'cn_code' && $value == 0) {
            $transport_item = TransportOrderItem::select('cn_code', 'kg', 'warehouse_cn_date')->where('cn_code', $value)->first();
            $res['weight'] = $transport_item->kg;
            $res['weight_date'] =  $transport_item->warehouse_cn_date;
        }

        OrderItem::find($data['pk'])->update($res);

        return response()->json([
            'status'  => true,
            'message' => 'Lưu thành công !'
        ]);
    }


}
