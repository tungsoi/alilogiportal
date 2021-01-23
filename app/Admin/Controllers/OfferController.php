<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Extensions\OffersExporter;
use App\Admin\Actions\OrderItem\Ordered;
use App\Admin\Actions\OrderItem\WarehouseVietnam;
use App\Admin\Actions\Extensions\OrdersExporter;
use App\Models\Alilogi\TransportRecharge;
use App\Models\Alilogi\Warehouse;
use App\Models\OrderItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\PurchaseOrder;
use App\User;
use DateTime;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Thống kê đàm phán';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PurchaseOrder());
        $grid->model()->whereOrderType(1)->where('status', '!=', PurchaseOrder::STATUS_UNSENT)->orderBy('created_at', 'desc');

        if (Admin::user()->isRole('order_staff')) 
        {
            $grid->model()->where('supporter_order_id', Admin::user()->id);
        }

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->like('order_number', 'Mã đơn hàng');
                $filter->equal('customer_id', 'Mã khách hàng')->select(User::whereIsCustomer(1)->get()->pluck('symbol_name', 'id'));
                $filter->where(function ($query) {
                    $query->where('order_at', '>=', $this->input." 00:00:00");
                }, 'Ngày đặt hàng nhỏ nhất', 'order_at_begin')->date();

                $filter->where(function ($query) {
                    $query->where('order_at', '<=', $this->input." 23:59:59");
                }, 'Ngày đặt hàng lớn nhất', 'order_at_finish')->date();
            });
            $filter->column(1/2, function ($filter) {
                $filter->equal('status', 'Trạng thái')->select(PurchaseOrder::STATUS);
                $order_ids = DB::connection('aloorder')->table('admin_role_users')->where('role_id', 4)->get()->pluck('user_id');
                $filter->equal('supporter_order_id', 'Nhân viên đặt hàng')->select(User::whereIn('id', $order_ids)->pluck('name', 'id'));
                $filter->where(function ($query) {
                    $query->where('success_at', '>=', $this->input." 00:00:00");
                }, 'Ngày thành công nhỏ nhất', 'success_at_begin')->date();

                $filter->where(function ($query) {
                    $query->where('success_at', '<=', $this->input." 23:59:59");
                }, 'Ngày thành công lớn nhất', 'success_at_finish')->date();
            });
        });

        // $grid->fixColumns(5);
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order_number('Mã đơn hàng');
        $grid->current_rate('Tỷ giá');
        $grid->customer_id('Mã khách hàng')->display(function () {
            return $this->customer->symbol_name ?? "";
        });
        $grid->status('Trạng thái')->display(function () {
            $count = "";
            if ($this->status == PurchaseOrder::STATUS_DEPOSITED_ORDERING) {
                $count = "( ".$this->totalOrderedItems() . " / " . $this->sumQtyRealityItem()." )";
            } else if ($this->status == PurchaseOrder::STATUS_IN_WAREHOUSE_VN) {
                $count = "( ".$this->totalWarehouseVietnamItems() . " / " . $this->sumQtyRealityItem()." )";
            }

            return "<span class='label label-".PurchaseOrder::LABEL[$this->status]."'>".PurchaseOrder::STATUS[$this->status]." " .$count. "</span>";
        });
        $grid->order_at('Ngày đặt')->width(100);
        $grid->supporter_order_id('Nhân viên Order')->display(function () {
            return $this->supporterOrder->name ?? "";
        });
        $grid->purchase_total_items_price('Tiền thực đặt (Tệ)')->display(function () {
            return $this->sumQtyRealityMoney();
        })->totalRow(function ($amount) {
            return "<span id='purchase_total_items_price'></span>";
        });
        $grid->purchase_order_transport_fee('Tổng phí VCNĐ (Tệ)')->display(function () {
            if ($this->items) {
                $total = 0;
                foreach ($this->items as $item) {
                    if ($item->status != OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                        $total += $item->purchase_cn_transport_fee;
                    }
                    
                }

                return number_format($total, 2);
            }

            return 0;
        })->width(100)
        ->totalRow(function ($amount) {
            return "<span id='purchase_order_transport_fee'></span>";
        });
        $grid->column('total_reality', 'Tổng tiền thực đặt')->display(function() {
            if ($this->items) {
                $total = 0;
                foreach ($this->items as $item) {
                    if ($item->status != OrderItem::STATUS_PURCHASE_OUT_OF_STOCK) {
                        $total += $item->purchase_cn_transport_fee;
                    }
                    
                }

                return number_format($this->sumQtyRealityMoney() + $total, 2);
            }
        });
        $grid->final_payment('Tiền thanh toán (Tệ)')->display(function () {
            return $this->final_payment;
        })->editable()->totalRow();
        $grid->column('offer_cn', 'Chiết khấu (Tệ)')->display(function () {
            return number_format($this->offer_cn);
        })->totalRow();
        $grid->column('offer_vnd', 'Chiết khấu (VND)')->display(function () {
            return number_format($this->offer_vnd);
        })->totalRow();
        $grid->internal_note('Ghi chú nội bộ')->editable();
        // export
        $grid->exporter(new OffersExporter());

        // setup
        $grid->disableActions();
        $grid->disableBatchActions();
        $grid->paginate(50);
        $grid->disableCreateButton();

        Admin::script(
            <<<EOT

            $('tfoot').each(function () {
                $(this).insertAfter($(this).siblings('thead'));
            });

            $( document ).ready(function() {
                // Tiền thực đặt (Tệ)
                let purchase_total_items_price = $('tbody .column-purchase_total_items_price');

                let total = 0;
                let total_2 = 0;
                purchase_total_items_price.each( function( i, el ) {
                    var elem = $( el );
                    let html = parseFloat($.trim(elem.html()));
                    total += html;
                    total_2 += html;
                });
                total = total.toFixed(2);
                $('#purchase_total_items_price').html(total);


                // Tổng phí VCNĐ (Tệ)
                let purchase_order_transport_fee = $('tbody .column-purchase_order_transport_fee');
                let total_1 = 0;
                purchase_order_transport_fee.each( function( i, el ) {
                    var elem = $( el );
                    let html = parseFloat($.trim(elem.html()));
                    total_1 += html;
                    total_2 += html;
                });
                total_1 = total_1.toFixed(2);
                $('#purchase_order_transport_fee').html(total_1);


                // Tổng tiền thực đặt
                total_2 = total_2.toFixed(2);
                $('#purchase_order_transport_fee').parent().next().html(total_2);


                // Tiền thanh toán (Tệ)

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
        $show = new Show(PurchaseOrder::findOrFail($id));

        $show->field('id', trans('admin.id'));
        $show->title(trans('admin.title'));
        $show->order(trans('admin.order'));
        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
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
       
        $form->text('final_payment');

        return $form;
    }

    public function editable(Request $request) {
        DB::beginTransaction();
        
        try {
            $data = $request->all();
            $order = PurchaseOrder::find($data['pk']);

            if ($data['name'] == 'final_payment') {
                $order->final_payment = $data['value'];
                $order->save();
    
                if ($order->items) {
                    $total = 0;
                    foreach ($order->items as $item) {
                        $total += $item->purchase_cn_transport_fee;
                    }
                }
    
                if ($data['value'] == 0) {
                    $offer_cn = 0;
                    $offer_vnd = 0;
                }
                else {
                    $offer_cn = $order->sumQtyRealityMoney() + $total - $order->final_payment;
                    $offer_vnd = round($offer_cn * $order->current_rate);
                }
    
                $order->offer_cn = $offer_cn;
                $order->offer_vnd = $offer_vnd;
                $order->save();
    
                DB::commit();
    
                return response()->json([
                    'status'    =>  true,
                    'message'   =>  'Cập nhật tiền thanh toán thành công'
                ]);
            } else {
                $order->update([
                    $data['name']   =>  $data['value']
                ]);

                DB::commit();

                return response()->json([
                    'status'    =>  true,
                    'message'   =>  'Cập nhật thành công'
                ]);
            }
            
        }
        catch (\Exception $e) {

            DB::rollBack();

             return response()->json([
                'status'    =>  true,
                'message'   =>  $e->getMessage()
            ]);
        }
    }

    public function updateOrder(Request $request) {
        DB::beginTransaction();
        
        try {
            $data = $request->all();
            $order = PurchaseOrder::find($data['order_id']);
            $order->final_payment = $data['final_payment'];
            $order->save();

            if ($order->items) {
                $total = 0;
                foreach ($order->items as $item) {
                    $total += $item->purchase_cn_transport_fee;
                }
            }

            if ($data['final_payment'] == 0) {
                $offer_cn = 0;
                $offer_vnd = 0;
            }
            else {
                $offer_cn = $order->sumQtyRealityMoney() + $total - $order->final_payment;
                $offer_vnd = $offer_cn * $order->current_rate;
            }

            if ($offer_cn < 0) {
                $offer_cn = $offer_vnd = 0;
                $order->offer_cn = 0;
                $order->offer_vnd = 0;
                $order->save();
            }
            else {
                $order->offer_cn = $offer_cn;
                $order->offer_vnd = $offer_vnd;
                $order->save();
            }

            DB::commit();

            admin_toastr('Cập nhật tiền thanh toán thành công', 'success');

            return redirect()->back();
        }
        catch (\Exception $e) {

            DB::rollBack();

            admin_toastr($e->getMessage(), 'error');
            return redirect()->back();
        }
    }
}
