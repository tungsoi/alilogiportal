<?php

namespace App\Admin\Controllers;

use App\Models\Alilogi\Warehouse;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\User;
use App\Admin\Actions\Customer\Recharge;
use App\Admin\Actions\Customer\RechargeHistory;
use App\Models\Alilogi\District;
use App\Models\Alilogi\Province;
use App\Models\Alilogi\TransportRecharge;
use App\Models\Order;
use App\Models\TransportOrderItem;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Table;

class CustomerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Khách hàng';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User);
        $grid->model()->where('is_customer', 1)->orderBy('id', 'desc');
        
        $sales = DB::connection('aloorder')->table('admin_role_users')->where('role_id', 3)->get()->pluck('user_id');
        $saleStaff = User::whereIn('id', $sales)->get()->pluck('name', 'id');

        $grid->filter(function($filter) use($saleStaff) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) use ($saleStaff) {
                $filter->like('name', 'Họ và tên');
                $filter->like('symbol_name', 'Mã KH');
                $filter->where(function ($query) {
                    switch ($this->input) {
                        case 0:
                            $query->where('wallet', '<', 0);
                            break;
                        case 1:
                            $query->where('wallet', '>', 0);
                            break;
                        case 2:
                            $query->where('wallet', '<', 0)->orWhere('wallet', '>=', 0);
                            break;
                    }
                }, 'Số dư tài khoản', 'wallet')->select(['Công nợ', 'Số dư', 'Tất cả'])->default(2);
                $filter->equal('staff_sale_id', 'Nhân viên kinh doanh')->select($saleStaff);
            });
            $filter->column(1/2, function ($filter) {
                $filter->like('email');
                $filter->like('phone_number', 'SDT');
                $filter->equal('ware_house_id', 'Kho')->select(Warehouse::where('is_active', 1)->get()->pluck('name', 'id'));
                $filter->equal('province', 'Tỉnh/thành phố')->select(Province::all()->pluck('name', 'province_id'));
                $filter->equal('district', 'Quận/huyện')->select(District::all()->pluck('name', 'district_id'));
            });
        });

        $grid->header(function ($query) {

            $owed = $query->sum('wallet');
            $color = $owed > 0 ? 'green' : 'red';

            return '<h4>Công nợ khách hàng hiện tại: <span style="color:'.$color.'">'. number_format($owed) ."</span> (VND)</h4>";
        });

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->symbol_name('Mã khách hàng')->label('warning');
        // $grid->username('Tên đăng nhập');
        $grid->name('Họ và tên')->editable();
        $grid->email()->editable();
        $grid->phone_number('SDT')->editable();
        $grid->ware_house_id('Kho')->display(function () {
            return $this->warehouse->name ?? "";
        });
        $grid->wallet('Số dư ví (VND)')->display(function () {
            if ($this->wallet > 0) {
                return '<span class="label label-success">'.number_format($this->wallet) ?? "0".'</span>';
            }

            return '<span class="label label-danger">'.number_format($this->wallet).'</span>';
        })->sortable();
        $grid->province('Tỉnh/thành phố')->display(function () {
            return Province::whereProvinceId($this->province)->first()->name ?? "";
        });
        $grid->district('Quận/huyện')->display(function () {
            return District::whereDistrictId($this->district)->first()->name ?? "";
        });
        $grid->address('Địa chỉ')->editable();
        $grid->is_active('Trạng thái')->display(function () {
            switch($this->is_active) {
                case 1: 
                    return  '<span class="">Hoạt động</span>';
                default:
                return  '<span class="label label-danger">Khoá</span>';
            }
        });
        $grid->note('Ghi chú')->editable();
        // if (Admin::user()->isRole('head_sale')) 
        // {
            $grid->staff_sale_id('Nhân viên kinh doanh')
            ->editable('select', $saleStaff);
        // } else {
        //     $grid->staff_sale_id('Nhân viên kinh doanh')->display(function () {
        //         return User::find($this->staff_sale_id)->symbol_name ?? "";
        //     });
        // }
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return $this->created_at != "" ? date('H:i | d-m-Y', strtotime($this->created_at)) : "";
        });
        $grid->paginate(30);
        // $grid->disableActions();
        $grid->actions(function ($actions) {
            $actions->add(new Recharge($this->row->id));
            $actions->add(new RechargeHistory($this->row->id));
            // $actions->add(new OrderHistory($this->row->id));
            // $actions->add(new OrderPayment($this->row->id));

            $actions->disableDelete();
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function(Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });
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
        $show = new Show(User::findOrFail($id));

        $show->id('ID');
        $show->name('Họ và tên');
        $show->symbol_name('Mã khách hàng');
        $show->email();
        $show->phone_number('SDT');
        $show->ware_house_id('Kho')->as(function () {
            return $this->warehouse->name ?? "";
        });
        $show->wallet('Số dư ví (VND)')->as(function () {
            return number_format($this->wallet);
        });
        $show->address('Địa chỉ')->editable();
        $show->is_active('Trạng thái')->as(function () {
            switch($this->is_active) {
                case 1: 
                    return  'Hoạt động';
                default:
                return  'Khoá';
            }
        });
        $show->note();
        $show->created_at(trans('admin.created_at'))->as(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $show->updated_at(trans('admin.updated_at'))->as(function () {
            return date('H:i | d-m-Y', strtotime($this->updated_at));
        });
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User);
        $form->column(1/2, function ($form) {
            $form->text('name', 'Họ và tên')->rules('required');
            $form->text('symbol_name', 'Mã khách hàng')
            ->creationRules(['required', 'unique:admin_users,symbol_name'])
            ->updateRules(['required', "unique:admin_users,symbol_name,{{id}}"]);
    
            $form->text('email')
            ->creationRules(['required', 'unique:admin_users,email'])
            ->updateRules(['required', "unique:admin_users,email,{{id}}"]);
            $form->hidden('is_customer')->default(1);

            $sales = DB::connection('aloorder')->table('admin_role_users')->where('role_id', 3)->get()->pluck('user_id');

            // if (Admin::user()->isRole('head_sale')) 
            // {
                $form->select('staff_sale_id', 'Nhân viên kinh doanh')->options(
                    User::whereIn('id', $sales)->get()->pluck('name', 'id')
                );
            // } else {
            //     $form->select('staff_sale_id', 'Nhân viên kinh doanh')->options(
            //         User::whereIn('id', $sales)->get()->pluck('symbol_name', 'id')
            //     )->disable();
            // }
        });
        
        $form->column(1/2, function ($form) {
            $form->text('phone_number', 'SDT');
            $form->select('ware_house_id', 'Kho')->options(Warehouse::where('is_active', 1)->get()->pluck('name', 'id'));
            $form->text('address', 'Địa chỉ');
            $form->select('is_active', 'Trạng thái')->options(User::STATUS)->default(1);
            $form->text('note');
        });

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        if (request()->route()->getActionMethod() == 'store') {
            $form->hidden('username');
            $form->hidden('password');
        }

        $form->saving(function (Form $form) {
            if (request()->route()->getActionMethod() == 'store') {
                $form->password = Hash::make('123456');
            }
            $form->username = $form->email;
        });

        $form->saved(function (Form $form) {
            DB::table('admin_role_users')->insert([
                'user_id'   =>  $form->model()->id,
                'role_id'   =>  2
            ]);
        });

        return $form;
    }

    public function rechargeForm($id) {
        $form = new Form(new TransportRecharge());
        $user = User::find($id);

        $form->setAction(route('admin.customers.rechargeStore'));
        $form->divider('Thông tin khách hàng');
        $form->display('name', 'Họ và tên')->default($user->name);
        $form->display('symbol_name', 'Mã khách hàng')->default($user->symbol_name);
        $form->display('email', 'Email')->default($user->email);
        $form->display('phone_number', 'Số điện thoại')->default($user->phone_number);
        $form->display('wallet', 'Số dư hiện tại')->default(number_format($user->wallet));
        $form->divider('Nạp tiền');
        $form->currency('money', 'Số tiền cần nạp')->rules('required|min:4')->symbol('VND')->digits(0);
        $form->select('type_recharge', 'Loại hành động')->options(TransportRecharge::RECHARGE)->default(1)->rules('required')
        ->help('Trường hợp Hoàn tiền hoặc trừ tiền, yêu cầu ghi rõ nội dung: Lý do, Đơn hàng, ...');
        $form->textarea('content', 'Nội dung')->default('...');
        $form->hidden('user_id_created')->default(Admin::user()->id);
        $form->hidden('customer_id')->default($id);
        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
            $tools->disableList();
            $tools->append('<a href="'.route('admin.customers.index').'" class="btn btn-sm btn-default" title="Danh sách">
                <i class="fa fa-list"></i>
                <span class="hidden-xs">&nbsp;Danh sách</span>
            </a>');
        });
        
        Admin::script(
            <<<EOT
            $('form').submit(function() {
                var c = confirm("Xac nhan nap tien cho Khach hang nay ?");
                return c; //you can just return c because it will be true or false
            });
EOT
    );
        return $form;
    }

    public function recharge($id, Content $content)
    {
        return $content
        ->header($this->title)
        ->description('Nạp tiền vào tài khoản')
        ->body($this->rechargeForm($id));
    }

    public function rechargeStore(Request $request) {
        DB::beginTransaction();
        try {
            $data = $request->all();
            TransportRecharge::create($data);

            $customer = User::find($data['customer_id']);
            $wallet = $customer->wallet;
            
            switch ($data['type_recharge']) {
                case TransportRecharge::DEDUCTION:
                    $customer->wallet = (int) $wallet - (int) $data['money'];
                    $customer->save();
                    break;
                default:
                    $customer->wallet = (int) $wallet + (int) $data['money'];
                    $customer->save();
                    break;
            }

            DB::commit();
            admin_success(TransportRecharge::RECHARGE[$data['type_recharge']], 'Số tiền: '.number_format($data['money'])." - Khách hàng: ".$customer->name." - Số dư ví sau giao dịch: ".number_format($customer->wallet)) ." (VND)";
        } catch (\Exception $e) {
            DB::rollBack();
            admin_error('Nạp tiền không thành công', $e->getMessage());
        }

        return redirect()->route('admin.customers.index');
    }

    public function rechargeHistory($id, Content $content)
    {
        return $content->header('Lịch sử giao dịch ví')
        ->description('Chi tiết')

        ->row(function (Row $row) use ($id) {
            $row->column(4, new InfoBox('Thông tin', 'users', 'primary', '#', User::find($id)->symbol_name));
            $row->column(4, new InfoBox('Số dư ví', 'users', 'danger', '#', number_format(User::find($id)->wallet)));
        })
        ->row(function (Row $row) use ($id)
        {
            // Tab thong tin chi tiet bao cao
            $row->column(12, function (Column $column)  use ($id)
            {
                $column->append((new Box('Chi tiết các giao dịch', $this->rechargeHistoryGrid($id))));
            });
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function rechargeHistoryGrid($id)
    {
        $res = TransportRecharge::where('money', ">", 0)
        ->where('customer_id', $id)
        ->orderBy('id', 'desc')
        ->get();

        $headers = ['STT', 'Ngày giao dịch', 'Nội dung giao dịch', 'Loại giao dịch', 'Số dư đầu kỳ (VND)', 'Trừ tiền (VND)', 'Nạp tiền (VND)', 'Số dư cuối kỳ (VND)'];

        $raw = [
            'order' =>  '',
            'payment_date'  =>  '',
            'type_recharge' =>  '',
            'content'   =>  '',
            'before_payment'    =>  '',
            'down'   =>  '',
            'up'    =>  '',
            'after_payment'
        ];
        $data = [];

        foreach ($res as $key => $record) {
            $type = $record->type_recharge;
            if (in_array($type, TransportRecharge::UP)) {
                $up = $record->money;
                $down = null;
                $flag = 'up';
            } else {
                $down = $record->money;
                $up = null;
                $flag = 'down';
            }

            $data[] = [
                'order' =>  $key + 1,
                'payment_date'  =>  date('H:i | d-m-Y', strtotime($record->created_at)),
                'type_recharge' =>  TransportRecharge::ALL_RECHARGE[$record->type_recharge],
                'content'   =>  $record->content,
                'before_payment'    =>  '',
                'down'   =>  $down,
                'up'    => $up,
                'after_payment' =>  '',
                'flag'  =>  $flag
            ];
        }

        $data = array_reverse($data);
        foreach ($data as $key => $raw) {
            if ($key == 0) {
                $data[0]['before_payment']  = 0;
                if ($data[0]['flag'] == 'up') {
                    $data[0]['after_payment'] = $data[0]['before_payment'] + $data[0]['up'];
                }
                else {
                    $data[0]['after_payment'] = $data[0]['before_payment'] - $data[0]['down'];
                }
            }
            else {
                $data[$key]['before_payment']  = $data[$key-1]['after_payment'];
                if ($data[$key]['flag'] == 'up') {
                    $data[$key]['after_payment'] = $data[$key]['before_payment'] + $data[$key]['up'];
                }
                else {
                    $data[$key]['after_payment'] = $data[$key]['before_payment'] - $data[$key]['down'];
                }
            }
        }

        $data = array_reverse($data);

        foreach ($data as $key => $last_raw) {

            $data[$key]['before_payment'] = $data[$key]['before_payment'] >= 0 
            ? "<span class='label label-success'>".number_format($data[$key]['before_payment'])."</span>"
            : "<span class='label label-danger'>".number_format($data[$key]['before_payment'])."</span>";

            $data[$key]['down'] = $data[$key]['down'] != null
            ? "<span class='label label-danger'>".number_format($data[$key]['down'])."</span>"
            : null;
            
            $data[$key]['up'] = $data[$key]['up'] != null
            ? "<span class='label label-success'>".number_format($data[$key]['up'])."</span>"
            : null;
        
            $data[$key]['after_payment'] = $data[$key]['after_payment'] >= 0 
            ? "<span class='label label-success'>".number_format($data[$key]['after_payment'])."</span>"
            : "<span class='label label-danger'>".number_format($data[$key]['after_payment'])."</span>";

            unset($data[$key]['flag']);
        }

        $table = new Table($headers, $data);

        return $table->render();
    }

    public function orderHistory($id, Content $content)
    {
        return $content
            ->header('Danh sách mã vận đơn')
            ->description('Danh sách')
            ->body($this->orderHistoryGrid($id));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function orderHistoryGrid($id)
    {
        $grid = new Grid(new TransportOrderItem);
        $grid->model()->where('transport_customer_id', $id)->orderBy('id', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->like('cn_code', 'Mã vận đơn');
                $filter->equal('user_id_updated', 'Người sửa')->select(User::where('is_customer', 0)->pluck('name', 'id'));
                $filter->where(function ($query) {
                    switch ($this->input) {
                        case TransportOrderItem::IS_PAYMENT:
                            $query->where('is_payment', true);
                            break;
                        case TransportOrderItem::NOT_PAYMENT_VN:
                            $query->where('warehouse_cn', true)->where('warehouse_vn', true)->where('is_payment', false);
                            break;
                        case TransportOrderItem::CN_REV:
                            $query->where('warehouse_cn', true)->where('warehouse_vn', false);
                            break;
                        case TransportOrderItem::VN_REV:
                            $query->where('warehouse_cn', true)->where('warehouse_vn', true);
                            break;
                    }
                }, 'Trạng thái', 'status')->select(TransportOrderItem::STATUS);
            });
            $filter->column(1/2, function ($filter) {
                $filter->between('warehouse_cn_date', 'Ngày về kho TQ')->date();
                $filter->between('warehouse_vn_date', 'Ngày về kho Hà Nội')->date();
            });
        });
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->transport_customer_id('Tên KH')->display(function () {
            return $this->customer->symbol_name ?? "";
        });
        $grid->cn_code('MVD');
        $grid->kg();
        $grid->product_width('Rộng (cm)');
        $grid->product_length('Dài (cm)');
        $grid->product_height('Cao (cm)');
        $grid->volume('V/6000')->display(function() {
            return str_replace('.00', '', $this->volume);
        });
        $grid->cublic_meter('M3')->display(function() {
            return str_replace('.000', '', $this->cublic_meter);
        });
        $grid->advance_drag('Ứng kéo (Tệ)');
        $grid->price_service('Giá VC')->display(function() {
            return number_format($this->price_service);
        });
        $grid->total_price('Tổng tiền (VND)')->display(function() {
            return number_format($this->total_price);
        });
        $grid->payment_type('Loại TT')->display(function() {
            if ($this->is_payment == 1) {
                return $this->paymentTypeText($this->payment_type);
            }

            return "";
        });
        $grid->is_payment('Trạng thái')->display(function() {
            switch ($this->is_payment)
            {
                case 1:
                    $msg = "Đã thanh toán";
                    return '<span class="label label-success">'.$msg.'</span> <br>' . date('H:i | d-m-Y', strtotime($this->transporting_vn_date));
                case 0 :
                    return '<span class="label label-danger">Chưa thanh toán</span>';
            }
        });
        $grid->user_id_updated('Người sửa')->display(function() {
            return $this->userUpdated->name ?? "";
        });
        $grid->warehouse_cn_date('Ngày về TQ')->display(function () {
            return $this->warehouse_cn_date != "" ? date('H:i | d-m-Y', strtotime($this->warehouse_cn_date)) : "";
        });
        $grid->warehouse_vn_date('Ngày về VN')->display(function () {
            return $this->warehouse_vn_date != "" ? date('H:i | d-m-Y', strtotime($this->warehouse_vn_date)) : "";
        });
        $grid->note('Ghi chú')->editable();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableBatchActions();
        $grid->paginate(50);

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append('<a href="'.route('customers.index').'" class="btn btn-sm btn-primary" title="Danh sách">
                <i class="fa fa-list"></i>
                <span class="hidden-xs">&nbsp;Danh sách</span>
            </a>');
        });
        return $grid;
    }

    public function orderPayment($id, Content $content)
    {
        return $content
            ->header('Danh sách đơn hàng đã thanh toán')
            ->description('Danh sách')
            ->body($this->orderPaymentGrid($id));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function orderPaymentGrid($customer_id)
    {
        $grid = new Grid(new Order());
        $grid->model()->where('order_type', 2)
        ->where('transport_customer_id', $customer_id)
        ->orWhere('payment_customer_id', $customer_id)
        ->orderBy('id', 'desc');

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->like('order_number', 'Mã đơn hàng');
            });
            $filter->column(1/2, function ($filter) {
                $filter->between('created_at', 'Ngày thanh toán')->date();
            });
        });
        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order_number('Mã đơn hàng');
        // $grid->transport_customer_id('Tên khách hàng')->display(function() {
        //     return $this->transportCustomer->symbol_name ?? "";
        // });

        $grid->payment_customer_id('KH Thanh toán')->display(function() {
            if ($this->payment_customer_id != "") {
                return $this->paymentCustomer->symbol_name ?? "";
            }
            return "";
        });
        $grid->items('Số MVD')->count();
        $grid->transport_kg('KG')->totalRow(function ($amount) {
            return "<span class='label label-success'>".number_format($amount)."</span>";
        });
        $grid->price_kg('Giá KG (VND)')->display(function() {
            return number_format($this->getPriceService($this, $this::KG));
        });
        $grid->transport_volume('V/6000')->display(function() {
            return $this->transport_volume != '0.0000' ? $this->transport_volume : 0;
        })->totalRow(function ($amount) {
            return "<span class='label label-success'>".number_format($amount)."</span>";
        });
        $grid->price_volume('Giá V/6000 (VND)')->display(function() {
            return number_format($this->getPriceService($this, $this::V));
        });
        $grid->transport_cublic_meter('M3')->totalRow(function ($amount) {
            return "<span class='label label-success'>".number_format($amount)."</span>";
        });
        $grid->price_cublic_meter('Giá M3 (VND)')->display(function() {
            return number_format($this->getPriceService($this, $this::M3));
        });
        $grid->final_total_price('Tổng tiền (VND)')->display(function() {
            $total = number_format($this->final_total_price);
            if ($this->final_total_price <= 0) {
                return '<span class="label label-danger">'.$total.'</span>';
            }

            return $total;
        })->totalRow(function ($amount) {
            return "<span class='label label-success'>".number_format($amount)."</span>";
        });
        
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $grid->user_created_id('Người tạo')->display(function () {
            return $this->userCreated->name ?? "";
        });
        $grid->internal_note('Ghi chú');
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->tools(function ($tools) {
            $tools->append('<a href="'.route('customers.index').'" class="btn btn-sm btn-primary" title="Danh sách">
                <i class="fa fa-list"></i>
                <span class="hidden-xs">&nbsp;Danh sách</span>
            </a>');
        });
        return $grid;
    }
}
