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
                }, 'Ngày đặt hàng', 'order_at_begin')->date();

                $filter->where(function ($query) {
                    $query->where('order_at', '<=', $this->input." 23:59:59");
                }, 'Ngày đặt hàng kết thúc', 'order_at_finish')->date();
            });
            $filter->column(1/2, function ($filter) {
                $filter->equal('status', 'Trạng thái')->select(PurchaseOrder::STATUS);
                $order_ids = DB::connection('aloorder')->table('admin_role_users')->where('role_id', 4)->get()->pluck('user_id');
                $filter->equal('supporter_order_id', 'Nhân viên đặt hàng')->select(User::whereIn('id', $order_ids)->pluck('name', 'id'));
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
            return number_format($this->sumQtyRealityMoney(), 2);
        });
        $grid->purchase_order_transport_fee('Tổng phí VCNĐ (Tệ)')->display(function () {
            if ($this->items) {
                $total = 0;
                foreach ($this->items as $item) {
                    try {
                        $total += $item->purchase_cn_transport_fee;
                    } 
                    catch (\Exception $e) {
                        // dd($item->order->order_number);
                    }
                    
                }

                return number_format($total, 2);
            }

            return 0;
        })->width(100);
        $grid->final_payment('Tiền thanh toán (Tệ)')->display(function () {
            return $this->final_payment;
        })->editable()->totalRow();
        $grid->column('offer_cn', 'Chiết khấu (Tệ)')->totalRow()->display(function () {
            return number_format($this->offer_cn, 2);
        });
        $grid->column('offer_vnd', 'Chiết khấu (VND)')->display(function () {
            return number_format($this->offer_vnd);
        })->totalRow(function ($amount) {
            return number_format($amount);
        });
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
                $offer_vnd = round($offer_cn * $order->current_rate);
            }

            $order->offer_cn = $offer_cn;
            $order->offer_vnd = $offer_vnd;
            $order->save();

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
