<?php

namespace App\Console\Commands;

use App\Models\Rongdo\Customer;
use App\Models\Rongdo\CustomerProfile;
use App\User;
use Illuminate\Console\Command;

class SyncUserFromRongdoToAlilogi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync-user:rongdo-to-alilogi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đồng bộ dữ liệu customer từ rồng đỏ sang alilogi';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $rongdo_customers = Customer::all();
        $create = $duplicate = 0;
        $id = "";
        foreach ($rongdo_customers as $customer) {
            $flag = User::whereEmail($customer->email)->first();

            if ($flag) {
                $id .= $customer->id . ", ";
                $duplicate++;
            } 
            else {
                $data = $this->formatData($customer->toArray());
                User::create($data);
            }
        }

        // echo $duplicate . "\n";
        // echo $create . "\n";

        dd($id);
        dd($rongdo_customers);
    }

    public function formatData($data)
    {
        # code...

        $profile = CustomerProfile::whereCustomerId($data['id'])->first();
        return [
            'name'  =>  $data['name'],
            'symbol_name'   =>  $data['symbol_name'],
            'email' =>  $data['email'],
            'is_customer'   =>  1,
            'phone_number'  =>  $data['phone_number'],
            'is_active' =>  1,
            'username'  =>  $data['email'],
            'password'  =>  $data['password'],
            'wallet'    =>  (int) $profile->remaining_amount,
            'address'   =>  $profile->address
        ];
    }

//     $form->text('name', 'Họ và tên')->rules('required');
//     $form->text('symbol_name', 'Mã khách hàng')
//     ->creationRules(['required', 'unique:admin_users,symbol_name'])
//     ->updateRules(['required', "unique:admin_users,symbol_name,{{id}}"]);

//     $form->text('email')
//     ->creationRules(['required', 'unique:admin_users,email'])
//     ->updateRules(['required', "unique:admin_users,email,{{id}}"]);
//     $form->hidden('is_customer')->default(1);
// });

// $form->column(1/2, function ($form) {
//     $form->text('phone_number', 'SDT');
//     $form->select('ware_house_id', 'Kho')->options(Warehouse::where('is_active', 1)->get()->pluck('name', 'id'));
//     $form->text('address', 'Địa chỉ');
//     $form->select('is_active', 'Trạng thái')->options(User::STATUS)->default(1);
//     $form->text('note');
// });

// $form->disableEditingCheck();
// $form->disableCreatingCheck();
// $form->disableViewCheck();

// if (request()->route()->getActionMethod() == 'store') {
//     $form->hidden('username');
//     $form->hidden('password');
// }

// $form->saving(function (Form $form) {
//     if (request()->route()->getActionMethod() == 'store') {
//         $form->password = Hash::make('123456');
//     }
//     $form->username = $form->email;
// });

// $form->saved(function (Form $form) {
//     DB::table('admin_role_users')->insert([
//         'user_id'   =>  $form->model()->id,
//         'role_id'   =>  2
//     ]);
// });
}
