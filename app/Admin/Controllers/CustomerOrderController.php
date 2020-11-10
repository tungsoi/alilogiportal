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
use Encore\Admin\Layout\Column;

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
                $column->append((new Box('Danh sách sản phẩm', $this->gridItem($id)->render())));
            });
        });
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
        ->whereOrderType(1)
        ->where('status', '!=', PurchaseOrder::STATUS_UNSENT)
        ->where('customer_id', Admin::user()->id)
        ->orderBy('created_at', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->like('order_number', 'Mã đơn hàng');
            $filter->equal('status', 'Trạng thái')->select(PurchaseOrder::STATUS);
            $filter->between('created_at', 'Ngày đặt hàng')->date();
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
            $symbol_name = $user->symbol_name != null ? $user->symbol_name : $user->email;
            
            $html = "<span class='label label-primary'>".$symbol_name."</span>";
            $html .= "<br> <i>" . number_format($user->wallet) . " (VND)</i>";

            return $html;
        });

        $grid->status('Trạng thái')->display(function () {
            $count = "";
            if ($this->status == PurchaseOrder::STATUS_DEPOSITED_ORDERING) {
                $count = "( ".$this->totalOrderedItems() . " / " . $this->sumQtyRealityItem()." )";
            } else if ($this->status == PurchaseOrder::STATUS_IN_WAREHOUSE_VN) {
                $count = "( ".$this->totalWarehouseVietnamItems() . " / " . $this->sumQtyRealityItem()." )";
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

                return number_format($total, 2);
            }

            return 0;
        })->totalRow(function ($amount) {
            $amount = number_format($amount, 2);
            return '<span class="">'.$amount.'</span>';
        });
        $grid->purchase_order_service_fee('Phí dịch vụ (Tệ)')->display(function () {
            $html = number_format($this->purchase_order_service_fee, 2);
            return $html;
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="">'.$amount.'</span>';
        });

        $grid->purchase_order_transport_fee('Tổng phí VCNĐ (Tệ)')->display(function () {
            if ($this->items) {
                $total = 0;
                foreach ($this->items as $item) {
                    $total += $item->purchase_cn_transport_fee;
                }

                return number_format($total, 2);
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
        });
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
                
                return number_format($total_bill, 2) . "<br> <i>" . number_format($total_bill * $this->current_rate, 2) . " (VND)</i>";
            }
            return 0;
        })->totalRow(function ($amount) {
            $amount = number_format($amount);
            return '<span class="">'.$amount.'</span>';
        })
        ->help('Tổng giá cuối = Tổng giá trị SP + Phí dịch vụ + Tổng phí VCNĐ');

        $grid->admin_note('Admin ghi chú');
        $grid->disableCreateButton();
        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();

            $actions->append('
                <a href="'.route('admin.customer_orders.show', $this->getKey()).'" class="grid-row-view btn btn-success btn-xs" data-toggle="tooltip" title="Xem chi tiết đơn hàng">
                    <i class="fa fa-eye"></i>
                </a>'
            );
        });

        // Admin::style('.btn {display: block;}');

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });
        $grid->paginate(20);

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
            // if ($item->status != OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                $qty_reality += $item->qty_reality;
                $qty += $item->qty;
                $purchase_cn_transport_fee += $item->purchase_cn_transport_fee;
                $total_price_reality += $item->qty_reality * $item->price;
            // }
        }

        $total_bill = ($total_price_reality + $purchase_cn_transport_fee + $order->purchase_order_service_fee);
        $current_rate = $order->current_rate;
        $headers = ['Thông tin', 'Giá trị', ''];
        $rows = [
            ['Tổng giá trị đơn hàng = Tổng tiền thực đặt + ship nội địa + dịch vụ', number_format($total_bill, 2) . " (Tệ)" . " = " . number_format($total_bill * $current_rate, 2) . " (VND)", 'Kho', $order->warehouse->name ?? "Đang cập nhật"],
            ['Tổng tiền thực đặt', number_format($total_price_reality, 2) . " (Tệ)" . " = " . number_format($total_price_reality * $current_rate, 2) . " (VND)", 'Nhân viên đặt hàng', $order->supporterOrder->name ?? "Đang cập nhật"],
            ['Tổng phí ship nội địa Trung Quốc', number_format($purchase_cn_transport_fee, 2) . " (Tệ)"  . " = " . number_format($purchase_cn_transport_fee * $current_rate, 2) . " (VND)", 'Nhân viên CSKH', $order->supporter->name ?? "Đang cập nhật"],
            ['Tổng phí dịch vụ', number_format($order->purchase_order_service_fee, 2) . " (Tệ)" . " = " . number_format($order->purchase_order_service_fee * $current_rate, 2) . " (VND)", 'Nhân viên Kho', $order->supporterWarehouse->name ?? "Đang cập nhật"],
            ['Tổng số lượng', $qty, '<b>'.$order->order_number.'</b> / <b>'.$order->customer->symbol_name.'</b>'],
            ['Tổng thực đặt', $qty_reality, 'Trạng thái đơn hàng', "<span class='label label-".PurchaseOrder::LABEL[$order->status]."'>".PurchaseOrder::STATUS[$order->status]."</span>"],  
            ['Ngày tạo:', date('H:i | d-m-Y', strtotime($order->created_at)), 'Tỷ giá', number_format($current_rate) . " (VND)"],
            ['Số tiền phải cọc', number_format($order->deposit_default, 2) . " (VND)"]
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
        //
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function gridItem($id)
    {
        $grid = new Grid(new OrderItem());
        $grid->model()->where('order_id',$id);

        // $grid->filter(function($filter) {
        //     $filter->expand();
        //     $filter->disableIdFilter();
        //     // $filter->column(1/2, function ($filter) {
        //         $filter->where(function ($query) {
        //             $orders = PurchaseOrder::where('order_number', 'like', "%{$this->input}%")->get()->pluck('id');
    
        //             $query->whereIn('order_id', $orders);
                
        //         }, 'Mã đơn hàng');
        //         // $filter->equal('customer_id', 'Mã khách hàng')->select(User::whereIsCustomer(1)->get()->pluck('symbol_name', 'id'));
        //     // });
        //     // $filter->column(1/2, function ($filter) {
        //         $filter->like('cn_code', 'Mã vận đơn');
        //         $filter->like('cn_order_number', 'Mã giao dịch');
        //     // });
            
        // });
        $grid->fixColumns(5);
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order()->order_number('Mã đơn hàng')->help('Mã đơn hàng mua hộ')->label('primary')->display(function () {
            $html = "<p class='label label-primary'>".$this->order->order_number."</p>";
            $customer = $this->order->customer->symbol_name ?? $this->order->customer->email;

            $html .= "<br> <p class='label label-info'>".$customer."</p>" ?? "";
            $html .= "<br> <p class='label label-".OrderItem::LABEL[$this->status]."'>".OrderItem::STATUS[$this->status]."</p>";
            $html .= "<br>" . date('H:i | d-m-Y', strtotime($this->created_at));

            return $html;
        });
        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 50, 'height' => 50]);
        $grid->status('Link sản phẩm')->display(function () {
            return '<b><a href="'.$this->product_link.'" target="_blank"> Link SP</a></b>';
        });
        $grid->product_size('Kích thước')->display(function () {
            return $this->product_size != "null" ? $this->product_size : null;
        });
        $grid->product_color('Màu');
        $grid->qty('Số lượng');
        $grid->qty_reality('Số lượng thực đặt');
        $grid->price('Giá (Tệ)');
        $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
            return $this->purchase_cn_transport_fee ?? 0;
        })->help('Phí vận chuyển nội địa Trung quốc');
        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = $this->qty_reality * $this->price + $this->purchase_cn_transport_fee ;
            return number_format($totalPrice, 2) ?? 0; 
        })->help('= Số lượng thực đặt x Giá (Tệ) + Phí vận chuyển nội địa (Tệ)');
        $grid->weight('Cân nặng (KG)')->help('Cân nặng lấy từ Alilogi');
        $grid->weight_date('Ngày vào KG')->help('Ngày vào cân sản phẩm ở Alilogi')->display(function () {
            return $this->weight_date != null ? date('Y-m-d', strtotime($this->weight_date)) : null;
        });
        $grid->cn_code('Mã vận đơn Alilogi');
        // $grid->cn_order_number('Mã giao dịch');
        $grid->customer_note('Khách hàng ghi chú')->style('width: 100px')->editable();
        $grid->admin_note('Admin ghi chú');

        $grid->disableCreateButton();
        $grid->disableActions();

        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        
        $grid->tools(function (Grid\Tools $tools) {
            // $tools->append(new Ordered());
            // $tools->append(new WarehouseVietnam());

            // $id = explode('/', request()->server()['REQUEST_URI'])[3];

            // $tools->append('<a class="btn-confirm-ordered btn btn-sm btn-warning" data-user="'.Admin::user()->id.'" data-id="'.$id.'"><i class="fa fa-check"></i> &nbsp; Xác nhận đã dặt hàng</a>');
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

        Admin::script(
            <<<EOT
            var bar = "bar";
            $(document).on('click', '.btn-confirm-ordered', function () {
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
}
