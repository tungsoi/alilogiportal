<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Order\ConfirmOrdered;
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

        $grid->disableFilter();
        $grid->disableColumnSelector();

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order()->order_number('Trạng thái')->help('Mã đơn hàng mua hộ')->label('primary')->display(function () {
            $html = "";
            $html .= "<p class='label label-".OrderItem::LABEL[$this->status]."'>".OrderItem::STATUS[$this->status]."</p>";
            $html .= "<br>" . date('H:i | d-m-Y', strtotime($this->created_at));
            $html .= '<br> <a href="'.$this->product_link.'" target="_blank"> Link sản phẩm</a>';

            return $html;
        });
        $grid->column('product_image', 'Ảnh sản phẩm')->lightbox(['width' => 50, 'height' => 50]);
        $grid->product_size('Kích thước')->display(function () {
            return $this->product_size != "null" ? $this->product_size : null;
        })->editable()->width(100);
        $grid->product_color('Màu')->editable()->width(100);
        $grid->qty('Số lượng')->editable();
        $grid->qty_reality('Số lượng thực đặt')->editable();
        $grid->price('Giá (Tệ)');
        $grid->purchase_cn_transport_fee('VCND TQ (Tệ)')->display(function () {
            return $this->purchase_cn_transport_fee;
        })->help('Phí vận chuyển nội địa Trung quốc')->editable();

        $grid->column('total_price', 'Tổng tiền (Tệ)')->display(function () {
            $totalPrice = $this->qty_reality * $this->price + $this->purchase_cn_transport_fee ;
            return number_format($totalPrice, 2) ?? 0; 
        })->help('= Số lượng thực đặt x Giá (Tệ) + Phí vận chuyển nội địa (Tệ)');
        
        $grid->cn_code('Mã vận đơn Alilogi')->editable();
        $grid->cn_order_number('Mã giao dịch')->editable();
        $grid->customer_note('Khách hàng ghi chú')->width(100)->editable();
        $grid->admin_note('Admin ghi chú')->editable()->width(100);

        $grid->disableCreateButton();

        $grid->setActionClass(\Encore\Admin\Grid\Displayers\Actions::class);
        
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new Ordered());
            $tools->append(new WarehouseVietnam());

            $id = explode('/', request()->server()['REQUEST_URI'])[3];
            $order = PurchaseOrder::find($id);

            if ($order->status == PurchaseOrder::STATUS_DEPOSITED_ORDERING) {
                $tools->append('<a class="btn-confirm-ordered btn btn-sm btn-warning" data-user="'.Admin::user()->id.'" data-id="'.$id.'" data-toggle="tooltip" title="Chuyển trạng thái của đơn hàng thành Đã đặt hàng"><i class="fa fa-check"></i> &nbsp; Chốt đặt hàng đơn</a>');
            }
            
            if ($order->status == PurchaseOrder::STATUS_NEW_ORDER) {
                $tools->append('<a href="'.route('admin.puchase_orders.deposite', $id).'" class="btn btn-sm btn-danger" data-toggle="tooltip" title="Vào tiền cọc cho đơn hàng" target="_blank"><i class="fa fa-money"></i> &nbsp; Vào tiền cọc cho đơn hàng</a>');
            }
            
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function ($actions) use ($id) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();
            
            $order = PurchaseOrder::find($id);
            if (in_array($order->status, [PurchaseOrder::STATUS_NEW_ORDER, PurchaseOrder::STATUS_DEPOSITED_ORDERING, PurchaseOrder::STATUS_ORDERED]))
            {
                $actions->append('
                    <a class="grid-row-outstock btn btn-danger btn-xs" data-id="'.$this->getKey().'">
                        <i class="fa fa-times"></i> Hết hàng
                    </a>'
                );
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
        # code...

        $data = $request->all();

        dd($data);
        OrderItem::find($data['pk'])->update([
            $data['name']   =>  $data['value']
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Lưu thành công !'
        ]);
    }


}
