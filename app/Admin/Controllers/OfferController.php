<?php

namespace App\Admin\Controllers;

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

        if (Admin::user()->isRole('sale_staff')) 
        {
            $customer = User::whereStaffSaleId(Admin::user()->id)->get()->pluck('id');
            $grid->model()->whereIn('customer_id', $customer);
        } 
        else if (Admin::user()->isRole('order_staff')) 
        {
            $grid->model()->where('supporter_order_id', Admin::user()->id);
        }

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->like('order_number', 'Mã đơn hàng');
                $filter->equal('customer_id', 'Mã khách hàng')->select(User::whereIsCustomer(1)->get()->pluck('symbol_name', 'id'));
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
        $grid->column('number', 'STT')->width(50);
        $grid->order_number('Mã đơn hàng')->width(150);
        $grid->customer_id('Mã khách hàng')->display(function () {
            return $this->customer->symbol_name ?? "";
        })->width(150);
        $grid->status('Trạng thái')->display(function () {
            $count = "";
            if ($this->status == PurchaseOrder::STATUS_DEPOSITED_ORDERING) {
                $count = "( ".$this->totalOrderedItems() . " / " . $this->sumQtyRealityItem()." )";
            } else if ($this->status == PurchaseOrder::STATUS_IN_WAREHOUSE_VN) {
                $count = "( ".$this->totalWarehouseVietnamItems() . " / " . $this->sumQtyRealityItem()." )";
            }

            return "<span class='label label-".PurchaseOrder::LABEL[$this->status]."'>".PurchaseOrder::STATUS[$this->status]." " .$count. "</span>";
        })->width(150);
        $grid->supporter_order_id('Nhân viên Order')->display(function () {
            return $this->supporterOrder->name ?? "";
        })->width(150);
        $grid->purchase_total_items_price('Tiền thực đặt (Tệ)')->display(function () {
            return number_format($this->sumQtyRealityMoney(), 2);
        })->width(150);
        $grid->final_payment('Tiền thanh toán (Tệ)')->display(function () {
            return number_format($this->final_payment);
        })->editable()->width(100);
        $grid->column('offer', 'Chiết khấu (Tệ / VND)')->display(function () {
            if ($this->final_payment != "" && $this->final_payment > 0) {
                try {
                    $amount = number_format($this->sumQtyRealityMoney() - $this->final_payment);
                    return $amount . " / " . number_format($amount * $this->current_rate);
                }
                catch (\Exception $e) {
                    return null;
                }
                
            }
            return null;
        })->width(100);

        // setup
        $grid->disableActions();
        $grid->disableBatchActions();
        $grid->paginate(50);
        $grid->disableCreateButton();

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
}
