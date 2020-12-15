<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\OrderItem\Ordered;
use App\Admin\Actions\OrderItem\WarehouseVietnam;
use App\Models\Alilogi\TransportOrderItem;
use App\Models\Alilogi\TransportRecharge;
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
use Illuminate\Support\Facades\DB;

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

        $order = PurchaseOrder::find($id);

        $grid->disableFilter();
        $grid->disableColumnSelector();

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order()->order_number('Trạng thái')->help('Mã đơn hàng mua hộ')->label('primary')->display(function () {
            $html = "";
            $html .= "<p class='label label-".OrderItem::LABEL[$this->status]."'>".OrderItem::STATUS[$this->status]."</p>";
            $html .= '<br> <a href="'.$this->product_link.'" target="_blank"> Link sản phẩm</a>';

            return $html;
        })->width(130);
        $grid->column('timeline', 'Timeline')->display(function () {
            $html = "<ul style='padding-left: 15px'>";
            
            $order_at = $this->order_at;
            $html .= "<li> Đặt hàng: ".$order_at."</li>";

            $item_logi = TransportOrderItem::whereCnCode($this->cn_code)->first();
            $warehouse_vn = $payment = "";
            if ($item_logi) {
                $warehouse_vn = $item_logi->warehouse_vn_date != "" ? date('Y-m-d', strtotime($item_logi->warehouse_vn_date)) : "--";

                if ($item_logi->order) {
                    $payment = date('Y-m-d', strtotime($item_logi->order->created_at));
                }

            }
            $html .= "<li> Về kho VN: ".$warehouse_vn."</li>";
            $html .= "<li> Xuất kho: ".$payment."</li>";

            return $html;
        })->width(200);
        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 70, 'height' => 70])->width(120);
       
        if ($order->status != PurchaseOrder::STATUS_SUCCESS) {
            $grid->product_size('Kích thước')->display(function () {
                return $this->product_size != "null" ? $this->product_size : null;
            })->editable()->width(100);
        } else {
            $grid->product_size('Kích thước')->display(function () {
                return $this->product_size != "null" ? $this->product_size : null;
            })->width(100);
        }
        
        if ($order->status != PurchaseOrder::STATUS_SUCCESS) {
            $grid->product_color('Màu')->editable()->width(100);
        } else {
            $grid->product_color('Màu')->width(100);
        }
        
        $grid->qty('Số lượng');

        if ($order->status != PurchaseOrder::STATUS_SUCCESS) {
            $grid->qty_reality('Số lượng thực đặt')->editable()->width(100);
        } else {    
            $grid->qty_reality('Số lượng thực đặt')->width(100);
        }

        $grid->price('Giá (Tệ)');

        if ($order->status != PurchaseOrder::STATUS_SUCCESS) {
            $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
                return $this->purchase_cn_transport_fee;
            })->help('Phí vận chuyển nội địa Trung quốc')->editable();
        } else {    
            $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
                return $this->purchase_cn_transport_fee;
            })->help('Phí vận chuyển nội địa Trung quốc');
        }
        

        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = $this->qty_reality * $this->price + $this->purchase_cn_transport_fee ;
            return number_format($totalPrice, 2) ?? 0; 
        })->help('= Số lượng thực đặt x Giá (Tệ) + Phí vận chuyển nội địa (Tệ)');
        
        if ($order->status != PurchaseOrder::STATUS_SUCCESS) {
            $grid->cn_code('Mã vận đơn Alilogi')->editable();
        } else {    
            $grid->cn_code('Mã vận đơn Alilogi');
        }

        if ($order->status != PurchaseOrder::STATUS_SUCCESS) {
            $grid->cn_order_number('Mã giao dịch')->editable();
        } else {    
            $grid->cn_order_number('Mã giao dịch');
        }
        
        
        if ($order->status != PurchaseOrder::STATUS_SUCCESS) {
            $grid->customer_note('Khách hàng ghi chú')->width(100)->editable();
        } else {    
            $grid->customer_note('Khách hàng ghi chú')->width(100);
        }
        
        if ($order->status != PurchaseOrder::STATUS_SUCCESS) {
            $grid->admin_note('Admin ghi chú')->editable()->width(100);
        } else {    
            $grid->admin_note('Admin ghi chú')->width(100);
        }
       

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        
        $grid->tools(function (Grid\Tools $tools) use ($order, $id) {

            // $id = explode('/', request()->server()['REQUEST_URI'])[3];

            // xac nhan dat hang san pham
            // hien thi khi co san pham o trang thai chua dat hang
            if ($order->items->where('status', OrderItem::STATUS_PURCHASE_ITEM_NOT_ORDER)->count() > 0) {
                $tools->append(new Ordered());
            }

            // xac nhan san pham da ve viet nam
            // hien thi khi tat ca san pham o trang thai da dat hang, khong tinh cac san pham het hang
            $all_items = $order->items->count();
            $ordered_items = $order->items->where('status', OrderItem::STATUS_PURCHASE_ITEM_ORDERED)->count();
            $outstock_items = $order->items->where('status', OrderItem::STATUS_PURCHASE_OUT_OF_STOCK)->count();
            if ($order->status == PurchaseOrder::STATUS_ORDERED || $order->status == PurchaseOrder::STATUS_IN_WAREHOUSE_VN) {
                //  && $all_items == $ordered_items + $outstock_items
                $tools->append(new WarehouseVietnam());
            }

            // xac nhan da dat hang ca don
            // hien thi khi tat ca san pham da duoc dat hang, khong tinh san pham het hang
            if ($all_items == $ordered_items + $outstock_items && $order->status == PurchaseOrder::STATUS_DEPOSITED_ORDERING) {
                $tools->append('<a class="btn-confirm-ordered btn btn-sm btn-warning" data-user="'.Admin::user()->id.'" data-id="'.$id.'" data-toggle="tooltip" title="Chuyển trạng thái của đơn hàng thành Đã đặt hàng"><i class="fa fa-check"></i> &nbsp; Chốt đặt hàng đơn</a>');
            }
            
            // if ($order->status == PurchaseOrder::STATUS_NEW_ORDER) {
            //     $tools->append('<a href="'.route('admin.puchase_orders.deposite', $id).'" class="btn btn-sm btn-danger" data-toggle="tooltip" title="Vào tiền cọc cho đơn hàng" target="_blank"><i class="fa fa-money"></i> &nbsp; Vào tiền cọc cho đơn hàng</a>');
            // }
            
            if ($order->status != PurchaseOrder::STATUS_SUCCESS && Admin::user()->can('confirm-order-success')) {
                $tools->append('<a class="btn-confirm-success btn btn-sm btn-success" data-user="'.Admin::user()->id.'" data-id="'.$id.'" data-toggle="tooltip" title="Chốt đơn hàng thành công"><i class="fa fa-check"></i> &nbsp; Chốt đơn hàng thành công</a>');
            }

            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->disableActions();
        $grid->actions(function ($actions) use ($order, $id) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();
            
            if (in_array($order->status, [PurchaseOrder::STATUS_NEW_ORDER, PurchaseOrder::STATUS_DEPOSITED_ORDERING, PurchaseOrder::STATUS_ORDERED]))
            {
                // $actions->append('
                //     <a class="grid-row-outstock btn btn-danger btn-xs" data-id="'.$this->getKey().'">
                //         <i class="fa fa-times"></i> Hết hàng
                //     </a>'
                // );
            }

        });
        $grid->paginate(200);
        $grid->disablePagination();
        Admin::style('.box {border-top:none;} .column-order.order_number span{margin-bottom: 10px;}');

        Admin::script(
            <<<EOT
            $(document).on('click', '.btn-confirm-ordered', function () {
                let flag_submit_ajax = false;
                Swal.fire({
                    title: 'Xác nhận đơn này đã đặt hàng ?',
                    showDenyButton: false,
                    showCancelButton: true,
                    confirmButtonText: `Đồng ý`,
                    cancelButtonText: 'Huỷ bỏ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (! flag_submit_ajax)
                        {
                            $.ajax({
                                type: 'POST',
                                url: '/api/confirm-ordered',
                                data: {
                                    order_id: $(this).data('id'),
                                    user_id_created: $(this).data('user')
                                },
                                success: function(response) {
                                    if (response.error == false) {
                                        Swal.fire('Đã xác nhận đặt hàng !', '', 'success');
            
                                        setTimeout(function () {
                                            window.location.reload();
                                        }, 100);
            
                                    } else {
                                        Swal.fire(response.msg, '', 'danger');

                                        // setTimeout(function () {
                                        //     window.location.reload();
                                        // }, 500);
                                    }
                                }
                            });
                        }
                    }
                    else {
                        return false;
                    }
                });
            });

            $(document).on('click', '.grid-row-outstock', function () {
                let flag_submit_ajax = false;
                Swal.fire({
                    title: 'Xác nhận sản phẩm này đã hết hàng ?',
                    showDenyButton: false,
                    showCancelButton: true,
                    confirmButtonText: `Đồng ý`,
                    cancelButtonText: 'Huỷ bỏ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (! flag_submit_ajax)
                        {
                            $.ajax({
                                type: 'POST',
                                url: '/api/confirm-outstock',
                                data: {
                                    item_id: $(this).data('id')
                                },
                                success: function(response) {
                                    if (response.error == false) {
                                        Swal.fire('Đã xác nhận hết hàng !', '', 'success');
            
                                        setTimeout(function () {
                                            window.location.reload();
                                        }, 100);
            
                                    } else {
                                        Swal.fire(response.msg, '', 'danger');

                                        // setTimeout(function () {
                                        //     window.location.reload();
                                        // }, 500);
                                    }
                                }
                            });
                        }
                    }
                    else {
                        return false;
                    }
                });
            });

            $(document).on('click', '.btn-confirm-success', function () {
                let flag_submit_ajax = false;
                Swal.fire({
                    title: 'Xác nhận đơn hàng này đã thành công ?',
                    showDenyButton: false,
                    showCancelButton: true,
                    confirmButtonText: `Đồng ý`,
                    cancelButtonText: 'Huỷ bỏ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (! flag_submit_ajax)
                        {
                            $.ajax({
                                type: 'POST',
                                url: '/api/confirm-order-success',
                                data: {
                                    id: $(this).data('id')
                                },
                                success: function(response) {
                                    if (response.error == false) {
                                        Swal.fire('Đã chốt đơn hàng thành công !', '', 'success');
            
                                        setTimeout(function () {
                                            window.location.reload();
                                        }, 100);
            
                                    } else {
                                        Swal.fire(response.msg, '', 'danger');

                                        // setTimeout(function () {
                                        //     window.location.reload();
                                        // }, 500);
                                    }
                                }
                            });
                        }
                    }
                    else {
                        return false;
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
        $role = 'admin';
        return view('admin.customer-detail-order', compact(
            'order', 'status', 'qty', 'qty_reality', 'total_price_reality', 'current_rate', 'purchase_cn_transport_fee',
            'total_bill', 'role'
        ))->render();
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
        DB::beginTransaction();

        try {
            # code...

            $data = $request->all();

            $item_id = $data['pk'];
            
            $item = OrderItem::find($item_id);
            $order = $item->order;

            if ($data['name'] == 'qty_reality') {

                // cap nhat so luong thuc dat
                if ($data['value'] == '0') {

                    // neu so luong thuc dat = 0 -> het hang san pham
                    // update thong tin san pham
                    $item->update([
                        $data['name']   =>  $data['value'],
                        'status'        =>  OrderItem::STATUS_PURCHASE_OUT_OF_STOCK
                    ]);

                    // check so luong san pham het hang trong don
                    if ($order->totalItems() == $order->totalItemOutStock()) {
                        // huy don hang
                        $order->update([
                            'status'    =>  PurchaseOrder::STATUS_CANCEL,
                            'purchase_order_service_fee'    =>  0
                        ]);

                        $customer = $order->customer;
                        $customer->wallet += $order->deposited;
                        $customer->save();

                        TransportRecharge::create([
                            'customer_id'       =>  $order->customer_id,
                            'user_id_created'   =>  1,
                            'money'             =>  $order->deposited,
                            'type_recharge'     =>  TransportRecharge::REFUND,
                            'content'           =>  'Huỷ đơn hàng, hoàn trả tiền cọc. Mã đơn hàng '.$order->order_number,
                            'order_type'        =>  TransportRecharge::TYPE_ORDER
                        ]);

                        DB::commit();

                        return response()->json([
                            'status'  => true,
                            'message' => 'Lưu thành công !'
                        ]);
                    }
                    else {
                        // cac truong hop khac
                        // slsp đã về vn + slsp hết hàng = tổng số sp

                        if ($order->totalItemOutStock() + $order->totalWarehouseVietnamItems() == $order->totalItems()) 
                        {
                            $order->status = PurchaseOrder::STATUS_SUCCESS;
                            $order->success_at = date('Y-m-d H:i:s', strtotime(now()));
                            $order->save();

                            if ($order->status == PurchaseOrder::STATUS_SUCCESS) {
                                $deposited = $order->deposited; // số tiền đã cọc
                                $total_final_price = round($order->totalBill() * $order->current_rate); // tổng tiền đơn hiện tại VND

                                $customer = $order->customer;
                                $wallet = $customer->wallet;

                                $flag = false;
                                if ($deposited <= $total_final_price)
                                {
                                    # Đã cọc < tổng đơn -> còn lại : tổng đơn - đã cọc
                                    # -> trừ tiền của khách số còn lại

                                    $owed = $total_final_price - $deposited;
                                    $customer->wallet = $wallet - $owed; 
                                    $customer->save();
                                    $flag = true;

                                    if ($flag) {
                                        TransportRecharge::firstOrCreate([
                                            'customer_id'       =>  $order->customer_id,
                                            'user_id_created'   =>  1,
                                            'money'             =>  $owed,
                                            'type_recharge'     =>  TransportRecharge::DEDUCTION,
                                            'content'           =>  'Thanh toán đơn hàng mua hộ. Mã đơn hàng '.$order->order_number,
                                            'order_type'        =>  TransportRecharge::TYPE_ORDER
                                        ]);

                                        DB::commit();
                                        return response()->json([
                                            'status'  => true,
                                            'message' => 'Lưu thành công !'
                                        ]);
                                    }
                                    
                                } else {

                                    # Đã cọc > tổng đơn 
                                    # -> còn lại: đã cọc - tổng đơn
                                    # -> cộng lại trả khách

                                    $owed = $deposited - $total_final_price;
                                    $customer->wallet = $wallet + $owed; 
                                    $customer->save();
                                    $flag = true;

                                    if ($flag) {
                                        TransportRecharge::firstOrCreate([
                                            'customer_id'       =>  $order->customer_id,
                                            'user_id_created'   =>  1,
                                            'money'             =>  $owed,
                                            'type_recharge'     =>  TransportRecharge::REFUND,
                                            'content'           =>  'Thanh toán đơn hàng mua hộ. Mã đơn hàng '.$order->order_number,
                                            'order_type'        =>  TransportRecharge::TYPE_ORDER
                                        ]);

                                        DB::commit();
                                        return response()->json([
                                            'status'  => true,
                                            'message' => 'Lưu thành công !'
                                        ]);
                                    }
                                }
                            }
                        }
                        else {
                            $data = PurchaseOrder::buildData($order->id);
                            $order->update($data);

                            DB::commit();

                            return response()->json([
                                'status'  => true,
                                'message' => 'Lưu thành công !'
                            ]);
                        }
                    }
                } else {

                    $item->update([
                        $data['name']   =>  $data['value']
                    ]);

                    // so luong thuc dat != 0 -> tinh lai tien don hang
                    $data = PurchaseOrder::buildData($order->id);
                    $order->update($data);

                    DB::commit();

                    return response()->json([
                        'status'  => true,
                        'message' => 'Lưu thành công !'
                    ]);
                }
            }
            else {
                // cap nhat cac thong tin khac
                $item->update([
                    $data['name']   =>  $data['value']
                ]);

                DB::commit();

                return response()->json([
                    'status'  => true,
                    'message' => 'Lưu thành công !'
                ]);
            }
        }
        catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Update lỗi : ' . $e
            ]);
        }
        
    }


}
