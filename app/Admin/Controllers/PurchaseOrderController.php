<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Order\Deposite;
use App\Admin\Actions\OrderItem\Ordered;
use App\Admin\Actions\OrderItem\WarehouseVietnam;
use App\Models\Alilogi\TransportRecharge;
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
use Encore\Admin\Widgets\Table;
use Illuminate\Http\Request;

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
        $grid->model()->whereOrderType(1)->orderBy('created_at', 'desc');

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

        $grid->fixColumns(6);
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order_number('Mã đơn hàng')->label('primary');
        $grid->customer_id('Mã khách hàng')->display(function () {
            return User::find($this->customer_id)->symbol_name ?? null;
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

        $grid->column('staff', 'Nhân viên phụ trách')->display(function () {
            $html = "<ul style='padding-left: 15px;'>";
            $html .= '<li>Đặt hàng: ' . ($this->supporterOrder->name ?? "...") . "</li>";
            $html .= '<li>CSKH: ' . ($this->supporter->name ?? "...") . "</li>";
            $html .= '<li>Kho: ' . ($this->supporterWarehouse->name ?? "...") . "</li>";
            $html .= "</ul>";

            return $html;
        });
        $grid->column('total_items', 'Số sản phẩm')->display(function () {
            return $this->totalItems();
        });
        $grid->purchase_total_items_price('Tổng giá sản phẩm (Tệ)')->display(function () {
            return number_format($this->purchase_total_items_price);
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="">'.$amount.'</span>';
        });
        $grid->purchase_order_service_fee('Phí dịch vụ (VND)')->display(function () {
            return number_format($this->purchase_order_service_fee);
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="">'.$amount.'</span>';
        })->editable();

        $grid->purchase_order_transport_fee('Phí VCNĐ (VND)')->display(function () {
            return number_format($this->purchase_order_transport_fee);
        })->editable();
        $grid->column('total_kg', 'Tổng KG')->display(function () {
            return $this->totalWeight();
        });
        $grid->column('price_weight', 'Giá KG (VND)')->display(function () {
            return number_format($this->price_weight);
        })->editable();
        $grid->warehouse()->name('Kho');
        $grid->deposited('Đã cọc (VND)')->display(function () {
            return number_format($this->deposited);
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="">'.$amount.'</span>';
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
            return '<span class="">'.$amount.'</span>';
        });
        $grid->admin_note('Admin ghi chú')->editable();
        $grid->internal_note('Nội bộ ghi chú')->editable();
        $grid->current_rate('Tỷ giá (VND)')->display(function () {
            return number_format($this->current_rate);
        });
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $grid->disableCreateButton();
        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();

            $actions->append('
                <a href="'.route('admin.detail_orders.show', $this->getKey()).'" class="grid-row-view btn btn-success btn-xs">
                    <i class="fa fa-eye"></i> &nbsp;Chi tiết đơn
                </a>'
            );

            $actions->append('
                <a href="'.route('admin.puchase_orders.edit', $this->getKey()).'" class="grid-row-edit btn btn-primary btn-xs">
                    <i class="fa fa-edit"></i> &nbsp;Chỉnh sửa
                </a>'
            );

            if ($this->row->status == PurchaseOrder::STATUS_NEW_ORDER) {
                $actions->append('
                    <a href="'.route('admin.puchase_orders.deposite', $this->getKey()).'" class="grid-row-deposite btn btn-info btn-xs">
                        <i class="fa fa-money"></i> &nbsp;Vào tiền cọc
                    </a>'
                );
            }
            
        });

        Admin::style('.btn {display: block;}');

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });
        $grid->paginate(50);

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
        $grid->id('Mã SP')->display(function () {
            return "SPMH-".str_pad($this->id, 5, 0, STR_PAD_LEFT);
        });
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
        $grid->qty('Số lượng')->editable();
        $grid->qty_reality('Số lượng thực đặt')->editable();
        $grid->price('Giá (Tệ)')->editable();
        $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
            return $this->purchase_cn_transport_fee ?? 0;
        })->help('Phí vận chuyển nội địa Trung quốc')->editable();
        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = $this->qty_reality * $this->price + $this->purchase_cn_transport_fee ;
            return number_format($totalPrice) ?? 0; 
        });
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

    public function deposite($id, Content $content) {
        return $content
            ->title($this->title)
            ->description("Đặt tiền cọc")
            ->body($this->formDeposite()->edit($id));
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function formDeposite()
    {
        $form = new Form(new PurchaseOrder);

        $form->setAction(route('admin.puchase_orders.postDeposite'));

        $form->divider("Thông tin");
        $form->display('order_number', 'Mã đơn hàng');
        $form->display('customer_name', 'Mã khách hàng');
        $form->select('warehouse_id', 'Kho')->options(Warehouse::all()->pluck('name', 'id'));
        $form->display('created_at', trans('admin.created_at'));

        $form->divider("Vào tiền cọc");
        $form->currency('final_total_price', 'Tổng giá cuối')
            ->symbol('VND')
            ->width(200)
            ->readonly();
        $form->currency('deposit_default', 'Số tiền phải cọc (70%)')
            ->symbol('VND')
            ->width(200)
            ->readonly();
        $form->currency('deposite', 'Số tiền đặt cọc')->rules(['required'])
            ->symbol('VND')
            ->width(200);
        $form->date('deposited_at', 'Ngày vào cọc')->default(now())->readonly();
        $form->text('staff_deposited', 'Nhân viên thực hiện')->default(Admin::user()->name)->readonly();
        $form->hidden('user_id_deposited')->default(Admin::user()->id);
        $form->hidden('id');
        $form->hidden('customer_id');

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
        });
        
        return $form;
    }

    public function postDeposite(Request $request)
    {
        # code...
        $deposite = (int) $request->deposite;

        PurchaseOrder::find($request->id)->update([
            'deposited' =>  $deposite,
            'user_id_deposited' =>  $request->user_id_deposited,
            'deposited_at'  =>  date('Y-m-d', strtotime(now())),
            'status'    =>  PurchaseOrder::STATUS_DEPOSITED_ORDERING
        ]);

        $alilogi_user = User::find($request->customer_id);
        $wallet = $alilogi_user->wallet;
        $alilogi_user->wallet = $wallet - $deposite;
        $alilogi_user->save();

        TransportRecharge::create([
            'customer_id'   =>  $request->customer_id,
            'user_id_created'   => $request->user_id_deposited,
            'money' =>  $deposite,
            'type_recharge' =>  TransportRecharge::DEPOSITE_ORDER,
            'content'   =>  'Đặt cọc đơn hàng mua hộ. Mã đơn hàng '.PurchaseOrder::find($request->id)->order_number,
            'order_type'    =>  TransportRecharge::TYPE_ORDER
        ]);

        admin_toastr('Vào cọc thành công !', 'success');
        return redirect()->route('admin.puchase_orders.index');

    }
}
