<?php

namespace App\Admin\Controllers;

use App\Models\Alilogi\TransportOrderItem;
use App\Models\ExchangeRate;
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
use Illuminate\Http\Request;

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
        $order = PurchaseOrder::find($id);
        if ($order->customer_id != Admin::user()->id) {
            admin_error('Đây không phải đơn hàng của bạn. Không có quyền truy cập.');

            return redirect()->route('admin.admin.home');
        }
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
            $filter->column(1/2, function ($filter) {
                $filter->like('order_number', 'Mã đơn hàng');
                $filter->equal('status', 'Trạng thái')->select(PurchaseOrder::STATUS);
            });
            $filter->column(1/2, function ($filter) {
                $filter->between('created_at', 'Ngày đặt hàng')->date();
            });
        });

        // $grid->fixColumns(6);
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order_number('Mã đơn hàng')->display(function () {
            $html = "<span class='label label-primary'>".$this->order_number."</span>";
            $user = User::find($this->customer_id);
            if ($user) {
                $symbol_name = $user->symbol_name;
                $html .= "<br> <span class='label label-primary'>".$symbol_name."</span>";
            }
        
            $html .= "<br> <i>Tỷ giá: ".number_format($this->current_rate)." (VND) </i>";
            $html .= "<br> <i>".date('H:i | d-m-Y', strtotime($this->created_at))."</i>";

            return $html;
        })->width(150);
        $grid->status('Trạng thái')->display(function () {
            $count = "";
            if ($this->status == PurchaseOrder::STATUS_DEPOSITED_ORDERING) {
                $count = "( ".$this->totalOrderedItems() . " / " . $this->sumQtyRealityItem()." )";
            } else if ($this->status == PurchaseOrder::STATUS_IN_WAREHOUSE_VN) {
                $count = "( ".$this->totalWarehouseVietnamItems() . " / " . $this->sumQtyRealityItem()." )";
            }

            $html = "<span class='label label-".PurchaseOrder::LABEL[$this->status]."'>".PurchaseOrder::STATUS[$this->status]." " .$count. "</span>";
            if ($this->deposited != "") {
                $html .= "<br> Đã cọc: ".number_format($this->deposited);
                $deposited_at = $this->deposited_at != null ? date('d-m-Y', strtotime($this->deposited_at)) : "";
                $html .= "<br> <i>Ngày cọc: ".$deposited_at."</i>";
            }
        
            return $html;
        })->width(180);

        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 120, 'height' => 120])
        ->display(function () {
            
            if (! $this->items->first()) {
                return null;
            }
            else {
                $route = "";

                if (substr( $this->items->first()->product_image, 0, 7 ) === "images/") {
                    $route = asset('storage/admin/'.$this->items->first()->product_image);
                } else {
                    $route = $this->items->first()->product_image;
                }
                return '<a href="'.$route.'" class="grid-popup-link">
                <img src="'.$route.'" style="max-width:120px;max-height:120px" class="img img-thumbnail"></a>';
            }
            });
        $grid->column('total_items', 'Số sản phẩm')->display(function () {
            return $this->totalItemReality();
        });
        $grid->purchase_total_items_price('Tổng giá trị SP (Tệ)')->display(function () {
            $html = number_format($this->sumQtyRealityMoney(), 2);
            if ($this->deposited == "") {
                $html .= "<br> <i>( Cần cọc ".number_format($this->deposit_default) . " VND)</i>";
            }
            
            return $html;
        })->totalRow(function ($amount) {
            $amount = number_format($amount, 2);
            return '<span class="">'.$amount.'</span>';
        })->width(200);
        $grid->purchase_order_service_fee('Phí dịch vụ (Tệ)')->display(function () {
            $html = number_format($this->purchase_order_service_fee, 2);
            return $html;
        })->totalRow(function ($amount) {
            $amount = number_format($amount, 2);
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
        })->width(200);
        $grid->column('total_kg', 'Tổng KG')->display(function () {
            if ($this->items) {
                $total = 0;
                foreach ($this->items as $item) {
                    $total += $item->weight;
                }

                $html = $total;
                $html .= "<br> <i>".number_format($this->price_weight * $total, 2) . "</i>";
                return $html;
            }

            return 0;
        });
        $grid->final_total_price('Tổng giá cuối (Tệ)')->display(function () {
            if ($this->items) {
                return number_format($this->totalBill(), 2) . "<br> <i>" . number_format($this->totalBill() * $this->current_rate) . " (VND)</i>";
            }
            return 0;
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
                <a href="'.route('admin.customer_orders.show', $this->getKey()).'" class="grid-row-view btn btn-success btn-xs">
                    <i class="fa fa-eye"></i> Chi tiết
                </a> <br>'
            );

            $order = PurchaseOrder::find($this->getKey());

            if ($order->status == PurchaseOrder::STATUS_NEW_ORDER) {
                $actions->append('
                    <a href="javascript:void(0);" data-id="'.$this->getKey().'" class="grid-row-delete btn btn-danger btn-xs btn-customer-delete mg-t-10">
                        <i class="fa fa-trash"></i> Xoá
                    </a>'
                );
            }
        });

        Admin::style('table {font-size: 14px;};');
        $grid->disableExport();
        $grid->disableColumnSelector();
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });
        $grid->paginate(200);
        $grid->disablePerPageSelector();
        $grid->disablePagination();

        Admin::script(
            <<<EOT

            $('.grid-popup-link').magnificPopup({"type":"image"});

            $('tfoot').each(function () {
                $(this).insertAfter($(this).siblings('thead'));
            });

            $(document).on('click', '.btn-customer-delete', function () {
                $.ajax({
                    type: 'POST',
                    url: '/api/customer-destroy',
                    data: {
                        order_id: $(this).data('id')
                    },
                    success: function(response) {
                        if (response.error == false) {
                            toastr.success('Đã xoá đơn hàng thành công.');

                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);

                        } else {
                            toastr.error('Xảy ra lỗi: ' + response.msg);
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
        $order = PurchaseOrder::find($id);

        $items = $order->items;
        $qty = $qty_reality = 0;
        $purchase_cn_transport_fee = 0; // Tổng phí ship nội địa TQ
        $total_price_reality = 0; // Tổng tiền thực đặt = Tổng thực đặt * giá 

        foreach ($items as $item) {
            if ($item->status != OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                $qty_reality += $item->qty_reality;
                $qty += $item->qty;
                $purchase_cn_transport_fee += $item->purchase_cn_transport_fee;
                $total_price_reality += $item->qty_reality * $item->price;
            }
        }

        $total_bill = ($total_price_reality + $purchase_cn_transport_fee + $order->purchase_order_service_fee);
        $current_rate = $order->current_rate;

        $status = "<span class='label label-".PurchaseOrder::LABEL[$order->status]."'>".PurchaseOrder::STATUS[$order->status]."</span>";
        return view('admin.customer-detail-order', compact(
            'order', 'status', 'qty', 'qty_reality', 'total_price_reality', 'current_rate', 'purchase_cn_transport_fee',
            'total_bill'
        ))->render();
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

        $grid->disableFilter();
        $grid->disableColumnSelector();
        $grid->disableExport();
        // $grid->fixColumns(3);
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order()->order_number('Mã đơn hàng')->help('Mã đơn hàng mua hộ')->label('primary')->display(function () {
            $html = "";
            $html .= "<p class='label label-".OrderItem::LABEL[$this->status]."'>".OrderItem::STATUS[$this->status]."</p> <br>";
            $html .= '<a href="'.$this->product_link.'" target="_blank"> Link sản phẩm</a>';

            $logi = TransportOrderItem::whereCnCode($this->cn_code)->first();

            if ($logi) {
                $html .= "<br> <br>";
                $html .= "<ul style='padding-left: 20px'>";
                $html .= "<li>Về Kho VN: <br>".date('H:i | d-m-Y', strtotime($logi->warehouse_vn_date))."</li>";
                if ($logi->order) {
                    $html .= "<li>Xuất Kho: ".date('H:i | d-m-Y', strtotime($logi->order->created_at))."</li>";
                }
               
                $html .= "</ul>";
            }

            return $html;
        });
        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 120, 'height' => 120]);
        $grid->product_size('Kích thước')->display(function () {
            return $this->product_size != "null" ? $this->product_size : null;
        })->width(100);
        $grid->product_color('Màu')->width(100);

        
        $order = PurchaseOrder::find($id);

        if ($order->status == PurchaseOrder::STATUS_NEW_ORDER) {
            $grid->qty('Số lượng')->editable();
        }
        else {
            $grid->qty('Số lượng');
        }
        $grid->qty_reality('Số lượng thực đặt');
       
        $grid->price('Đơn giá (Tệ)')->display(function () {
            $html = $this->price;
            $html .= "<br>  <i>" . number_format($this->price * $this->order->current_rate) . " (VND)</i>";
            return $html;
        });
        $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
            $html = $this->purchase_cn_transport_fee ?? 0;
            $html .= "<br>  <i>" . number_format($this->purchase_cn_transport_fee * $this->order->current_rate) . " (VND)</i>";
            return $html;
        })->help('Phí vận chuyển nội địa Trung quốc');
        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = $this->qty_reality * $this->price + $this->purchase_cn_transport_fee ;
            
            $html = number_format($totalPrice, 2) ?? 0; 
            $html .= "<br> <i>".number_format($totalPrice * $this->order->current_rate)." (VND)</i>";
            return $html;
        })->help('= Số lượng thực đặt x Giá (Tệ) + Phí vận chuyển nội địa (Tệ)');
        $grid->weight('Cân nặng (KG)')->help('Cân nặng lấy từ Alilogi')->display(function () {
            if ($this->cn_code != "") {
                $logi = TransportOrderItem::whereCnCode($this->cn_code)->first();
                if ($logi) {
                    $html = $logi->kg;
                    $html .= "<br> <i>".$logi->price_service."</i>";
                }
                else {
                    $html = "";
                }

                return $html;
            }

            return "";
            

            // $html = $logi-
            // return $logi->kg;
            // $weight = $this->weight != null ? $this->weight : 0;
            // $html = $weight;
            // $html .= "<br> <i>".number_format($weight * $this->order->price_weight) . " (VND) </i> <br>";
            // $date = $this->weight_date;
            // $html .= "<br> <i>".$date."</i>";
            // return $html;
        });
        $grid->cn_code('Mã vận đơn Alilogi');
        $grid->customer_note('Ghi chú')->width(100)->editable();
        $grid->admin_note('Admin ghi chú');

        $grid->disableCreateButton();

        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        
        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();

            $item = OrderItem::find($this->getKey());
            if ($item->order->status == PurchaseOrder::STATUS_NEW_ORDER)
            {
                $actions->append('
                    <a class="btn btn-xs btn-danger btn-customer-delete-item-from-order" data-id="'.$this->getKey().'">
                        <i class="fa fa-trash"></i> Xoá
                    </a>'
                );
            }
        });

        $grid->disableBatchActions();
        $grid->tools(function (Grid\Tools $tools) {

            $id = explode('/', request()->server()['REQUEST_URI'])[3];
            $order = PurchaseOrder::find($id);
            if ($order && $order->status == PurchaseOrder::STATUS_NEW_ORDER) {
                $tools->append('<a class="btn-confirm-deposite btn btn-sm btn-warning" data-order="'.$id.'"><i class="fa fa-money"></i> &nbsp; Đặt cọc bằng số dư Tài khoản</a>');
            }

            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->paginate(200);
        $grid->disablePagination();
        Admin::style('.box {border-top:none;} table {font-size: 14px}');

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

            $(document).on('click', '.btn-confirm-deposite', function () {
                
                $.ajax({
                    type: 'POST',
                    url: '/api/customer-deposite',
                    data: {
                        order_id: $(this).data('order')
                    },
                    success: function(response) {
                        if (response.error == false) {
                            toastr.success('Đã đặt cọc thành công.');

                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);
                            
                        } else {
                            toastr.error('Xảy ra lỗi: ' + response.msg);
                        }
                    }
                });

            });

            $(document).on('click', '.btn-customer-destroy', function () {
                var isGood=confirm('Xác nhận Huỷ đơn hàng ?');
                if (isGood) {
                    $.ajax({
                        type: 'POST',
                        url: '/api/customer-destroy',
                        data: {
                            order_id: $(this).data('order')
                        },
                        success: function(response) {
                            if (response.error == false) {
                                toastr.success('Đã huỷ đơn hàng thành công.');

                                setTimeout(function () {
                                    window.location.reload();
                                }, 1000);
                                
                            } else {
                                toastr.error('Xảy ra lỗi: ' + response.msg);
                            }
                        }
                    });
                }
            });

            $(document).on('click', '.btn-customer-delete-item-from-order', function () {
                // $(this).remove();
                $.ajax({
                    type: 'POST',
                    url: '/api/customer-delete-item-from-order',
                    data: {
                        id: $(this).data('id')
                    },
                    success: function(response) {
                        if (response.error == false) {
                            toastr.success('Lưu thành công.');

                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);
                            
                        } else {
                            toastr.error('Xảy ra lỗi: ' + response.msg);
                        }
                    }
                });

            });
EOT
        );

        return $grid;
    }

    public function editable(Request $request)
    {
        # code...

        $data = $request->all();
        $item = OrderItem::find($data['pk']);

        if ($data['name'] == 'qty') {
            $item->qty = $data['value'];
            $item->qty_reality = $data['value'];
            $item->save();

            $order = PurchaseOrder::find($item->order_id);
            $purchase_total_items_price = 0;
            foreach ($order->items as $item) {
                $purchase_total_items_price += $item->qty_reality * $item->price;
            }

            $exchange_rate = ExchangeRate::first()->vnd;
            $final_total_price = round($purchase_total_items_price * $exchange_rate); // vnd
            $deposit_default   = round($final_total_price * 70 / 100); // vnd

            $order->purchase_total_items_price = $purchase_total_items_price;
            $order->final_total_price = $final_total_price;
            $order->deposit_default = $deposit_default;
            $order->save();
        } 
        else {
            OrderItem::find($data['pk'])->update([
                $data['name']   =>  $data['value']
            ]);
        }



        return response()->json([
            'status'  => true,
            'message' => 'Lưu thành công !'
        ]);
    }

}
