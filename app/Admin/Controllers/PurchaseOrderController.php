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
                $filter->between('created_at', 'Ngày đặt hàng')->date();
            });
        });

        $grid->fixColumns(6);
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order_number('Mã đơn hàng')->display(function () {
            $html = "<span class='label label-primary'>".$this->order_number."</span>";
            $html .= "<br> <i>Tỷ giá: ".number_format($this->current_rate)." (VND) </i>";
            $html .= "<br> <i>".date('H:i | d-m-Y', strtotime($this->created_at))."</i>";

            return $html;
        });
        $grid->customer_id('Mã khách hàng')->display(function () {
            $user = User::find($this->customer_id);
            $html = "<span class='label label-primary'>".$user->symbol_name."</span>" ?? null;
            $html .= "<br> <i>" . number_format($user->wallet) . " (VND)</i>";

            return $html;
        });

        $grid->status('Trạng thái')->display(function () {
            $count = "";
            if ($this->status == PurchaseOrder::STATUS_DEPOSITED_ORDERING) {
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
            return $this->totalItemReality();
        });
        $grid->purchase_total_items_price('Tổng giá trị SP (Tệ)')->display(function () {
            if ($this->items) {
                $total = 0;
                foreach ($this->items as $item) {
                    $total += $item->qty_reality * $item->price;
                }

                return number_format($total);
            }

            return 0;
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="">'.$amount.'</span>';
        });
        $grid->purchase_order_service_fee('Phí dịch vụ (Tệ)')->display(function () {
            $html = number_format($this->purchase_order_service_fee);
            return $html;
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="">'.$amount.'</span>';
        })->editable();

        $grid->purchase_order_transport_fee('Tổng phí VCNĐ (Tệ)')->display(function () {
            if ($this->items) {
                $total = 0;
                foreach ($this->items as $item) {
                    $total += $item->purchase_cn_transport_fee;
                }

                return number_format($total);
            }

            return 0;
        });
        $grid->column('total_kg', 'Tổng KG')->display(function () {
            if ($this->items) {
                $total = 0;
                foreach ($this->items as $item) {
                    $total += $item->weight;
                }

                return $total;
            }

            return 0;
        });
        $grid->column('price_weight', 'Giá KG (VND)')->display(function () {
            return number_format($this->price_weight);
        })->editable();
        $grid->warehouse()->name('Kho');
        $grid->deposited('Đã cọc (VND)')->display(function () {
            $html = number_format($this->deposited);
            $deposited_at = $this->deposited_at != null ? date('d-m-Y', strtotime($this->deposited_at)) : "";
            $html .= "<br> <i>".$deposited_at."</i>";

            return $html;
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="">'.$amount.'</span>';
        });
        $grid->final_total_price('Tổng giá cuối (Tệ)')->display(function () {
            if ($this->items) {
                $total = $total_transport = 0;
                foreach ($this->items as $item) {
                    $total += $item->qty_reality * $item->price; // tong gia san pham
                    $total_transport += $item->purchase_cn_transport_fee; // tong phi ship
                }

                $total_bill = ($total + $total_transport + $this->purchase_order_service_fee);
                
                return number_format($total_bill) . "<br> <i>" . number_format($total_bill * $this->current_rate) . " (VND)</i>";
            }
            return 0;
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="">'.$amount.'</span>';
        })
        ->help('Tổng giá cuối = Tổng giá trị SP + Phí dịch vụ + Tổng phí VCNĐ');

        $grid->final_payment('Tổng thanh toán (VND)')->display(function () {
            return number_format($this->final_payment);
        })->editable();

        $grid->admin_note('Admin ghi chú')->editable();
        $grid->internal_note('Nội bộ ghi chú')->editable();
        $grid->disableCreateButton();
        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();

            $actions->append('
                <a href="'.route('admin.detail_orders.show', $this->getKey()).'" class="grid-row-view btn btn-success btn-xs" data-toggle="tooltip" title="Xem chi tiết đơn hàng">
                    <i class="fa fa-eye"></i>
                </a>'
            );

            $actions->append('
                <a href="'.route('admin.puchase_orders.edit', $this->getKey()).'" class="grid-row-edit btn btn-primary btn-xs" data-toggle="tooltip" title="Chỉnh sửa đơn hàng">
                    <i class="fa fa-edit"></i>
                </a>'
            );

            if ($this->row->status == PurchaseOrder::STATUS_NEW_ORDER) {
                $actions->append('
                    <a href="'.route('admin.puchase_orders.deposite', $this->getKey()).'" class="grid-row-deposite btn btn-info btn-xs" data-toggle="tooltip" title="Đặt cọc tiền">
                        <i class="fa fa-money"></i>
                    </a>'
                );
            }

            $actions->append('
                <a href="'.route('admin.customers.recharge', $this->row->customer_id).'" class="grid-row-edit btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Nạp tài khoản khách hàng">
                    <i class="fa fa-plus"></i>
                </a>'
            );

            $order = PurchaseOrder::find($this->getKey());
            if ($this->row->status == PurchaseOrder::STATUS_DEPOSITED_ORDERING && $order->orderedItems() == $order->totalItems()) {
                $actions->append('
                    <a data-id="'.$this->getKey().'" data-user="'.Admin::user()->id.'"  class="grid-row-confirm-ordered btn btn-info btn-xs" data-toggle="tooltip" title="Xác nhận đã đặt hàng">
                        <i class="fa fa-check"></i>
                    </a>'
                );
            }
            

            if ($this->row->status != PurchaseOrder::STATUS_CANCEL && $this->row->status != PurchaseOrder::STATUS_SUCCESS) {
                $actions->append('
                    <a data-id="'.$this->getKey().'" data-user="'.Admin::user()->id.'" class="grid-row-cancle btn btn-danger btn-xs" data-toggle="tooltip" title="Huỷ đơn hàng">
                        <i class="fa fa-times"></i>
                    </a>'
                );
            }
  
        });

        // Admin::style('.btn {display: block;}');

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });
        $grid->paginate(50);

        Admin::script(
            <<<EOT

            $('tfoot').each(function () {
                $(this).insertAfter($(this).siblings('thead'));
            });

            var bar = "bar";
            $(document).on('click', '.grid-row-cancle', function () {
                var foo = bar;
                if ( foo == "bar" ) {
                    var isGood=confirm('Xác nhận Huỷ đơn hàng ?');
                    if (isGood) {
                        $.ajax({
                            type: 'POST',
                            url: '/api/cancle-purchase-order',
                            data: {
                                order_id: $(this).data('id'),
                                user_id_created: $(this).data('user')
                            },
                            success: function(response) {
                                if (response.error == false) {
                                    alert('Đã huỷ đơn hàng thành công.');

                                    setTimeout(function () {
                                        window.location.reload();
                                    }, 1000);
                                    
                                } else {
                                    alert('Xảy ra lỗi: ' + response.msg);
                                }
                            }
                        });
                    }
                }
            });

            var bar = "bar";
            $(document).on('click', '.grid-row-confirm-ordered', function () {
                var foo = bar;
                if ( foo == "bar" ) {
                    var isGood=confirm('Xác nhận Đã đặt hàng đơn hàng này ?');
                    if (isGood) {
                        $.ajax({
                            type: 'POST',
                            url: '/api/confirm-ordered',
                            data: {
                                order_id: $(this).data('id'),
                                user_id_created: $(this).data('user')
                            },
                            success: function(response) {
                                if (response.error == false) {
                                    alert('Đã xác nhận đặt hàng thành công.');

                                    setTimeout(function () {
                                        window.location.reload();
                                    }, 1000);
                                    
                                } else {
                                    alert('Xảy ra lỗi: ' + response.msg);
                                }
                            }
                        });
                    }
                }
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
                ['Tổng tổng tiền sản phẩm', number_format($order->purchase_total_items_price), 'Kho', $order->warehouse->name ?? "Đang cập nhật"],
                ['Tổng tiền thực đặt', number_format($order->purchase_total_items_price), 'Nhân viên đặt hàng', $order->supporterOrder->name ?? "Đang cập nhật"],
                ['Tổng phí ship nội địa TQ', number_format($order->transport_fee), 'Nhân viên CSKH', $order->supporter->name ?? "Đang cập nhật"],
                ['Tổng số lượng', $qty, 'Nhân viên Kho', $order->supporterWarehouse->name ?? "Đang cập nhật"],
                ['Tổng thực đặt', $qty_reality],  
                ['Ngày tạo:', date('H:i | d-m-Y', strtotime($order->created_at)), 'Mã khách hàng', $order->customer->symbol_name],
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
        
        $id = explode('/', request()->server()['REQUEST_URI'])[3];
        $order = PurchaseOrder::find($id);
        $purchase_total_items_price = $order->getPurchaseTotalItemPrice();

        $form->column(1/2, function ($form) {
            $form->hidden('id');
            $form->divider("Thông tin");
            $form->display('order_number', 'Mã đơn hàng');
            $form->display('customer_name', 'Mã khách hàng');
            $form->select('warehouse_id', 'Kho')->options(Warehouse::all()->pluck('name', 'id'));
            $form->display('created_at', trans('admin.created_at'));
            $form->select('status', 'Trạng thái')->options(PurchaseOrder::STATUS);

            $form->divider("Nhân viên phụ trách");
            $form->select('supporter_order_id', 'Đặt hàng')->options(User::whereIsCustomer(0)->get()->pluck('name', 'id'));
            $form->select('supporter_id', 'Chăm sóc KH')->options(User::whereIsCustomer(0)->get()->pluck('name', 'id'));
            $form->select('support_warehouse_id', 'Quản lý kho')->options(User::whereIsCustomer(0)->get()->pluck('name', 'id'));
            
            $form->text('admin_note', 'Admin ghi chú');
            $form->text('internal_note', 'Ghi chú nội bộ');
            
        });
       
        $form->column(1/2, function ($form) use ($purchase_total_items_price, $order) {
            $form->divider("Tổng tiền");
            $form->currency('final_total_price', 'Tổng giá cuối (VND)')->symbol('VND')->width(200)->disable()->digits(0);
            $form->currency('final_payment', 'Tiền thanh toán (VND)')->symbol('VND')->width(200)->digits(0);
            $form->currency('deposit_default', 'Số tiền phải cọc (70%) (VND)')->readonly()->symbol('VND')->width(200)->digits(0);
            $form->currency('deposited', 'Số tiền đã cọc (VND)')->symbol('VND')->width(200)->readonly()->digits(0);
            $form->text('deposited_at', 'Ngày vào cọc')->readonly();

            $form->divider("Các khoản chi phí");
            $form->html("<h4 style='text-align: right'>".number_format($purchase_total_items_price)."</h4>", 'Tổng giá sản phẩm (Tệ)');
            // ->symbol('￥')->readonly()->width(200)->digits(0)
            // ->default(number_format($purchase_total_items_price));
            $form->currency('current_rate', 'Tỷ giá chuyển đổi (VND)')->symbol('VND')->readonly()->width(200)->digits(0);

            $form->divider();
            $form->select('purchase_service_fee_percent', '% phí dịch vụ')->options([
                '0% tổng tiền sản phẩm',
                '1% tổng tiền sản phẩm',
                '1.5% tổng tiền sản phẩm',
                '2% tổng tiền sản phẩm',
                '2.5% tổng tiền sản phẩm',
                '3% tổng tiền sản phẩm',
            ]); // tinh % khi chon gia tri
            $form->currency('purchase_order_service_fee', 'Phí dịch vụ (Tệ)')->symbol('￥')->width(200)->digits(0)->readonly();
            
            $form->divider();
            // $form->currency('purchase_order_transport_fee', 'Phí ship nội địa (VND)')->symbol('VND')->width(200)->digits(0);
            $form->currency('price_weight', 'Giá cân nặng (VND)')->symbol('VND')->width(200)->digits(0);
            
            $form->hidden('deposited_at');
        });

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
        $id = explode('/', request()->server()['REQUEST_URI'])[3];
        $order = PurchaseOrder::find($id);

        $form->setAction(route('admin.puchase_orders.postDeposite'));

        $form->column(1/2, function ($form) {
            $form->divider("Thông tin");
            $form->display('order_number', 'Mã đơn hàng');
            $form->display('customer_name', 'Mã khách hàng');
            $form->select('warehouse_id', 'Kho')->options(Warehouse::all()->pluck('name', 'id'));
            $form->display('created_at', trans('admin.created_at'));
        });

        $form->column(1/2, function ($form) use ($order) {
            $form->divider("Vào tiền cọc");
            $form->currency('wallet', 'Số dư ví khách hàng')
            ->default($order->customer->wallet)
            ->symbol('VND')
            ->width(200)
            ->readonly()
            ->digits(0);
            $form->currency('final_total_price', 'Tổng giá cuối')
                ->symbol('VND')
                ->width(200)
                ->readonly()
                ->digits(0);
            $form->currency('deposit_default', 'Số tiền phải cọc (70%)')
                ->symbol('VND')
                ->width(200)
                ->readonly()
                ->digits(0);
            $form->currency('deposite', 'Số tiền đặt cọc')->rules(['required'])
                ->symbol('VND')
                ->width(200)
                ->digits(0);
            $form->date('deposited_at', 'Ngày vào cọc')->default(now())->readonly();
            $form->text('staff_deposited', 'Nhân viên thực hiện')->default(Admin::user()->name)->readonly();
            $form->hidden('user_id_deposited')->default(Admin::user()->id);
            $form->hidden('id');
            $form->hidden('customer_id');
        });
    
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
