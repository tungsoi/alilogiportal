<?php

namespace App\Admin\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\User;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller {
    public function register() {
        if (Admin::user()) {
            return redirect()->route('admin.home');
        }
        return view('customer.auth.register');
    }

    public function postRegister(Request $request) {
        $data = $request->all();

        $this->validator($data)->validate();

        $data = [
            'username'  =>  $data['username'],
            'password'  =>  bcrypt($data['password']),
            'name'      =>  $data['symbol_name'],
            'email'     =>  $data['username'],
            'phone_number'  =>  $data['mobile'],
            'wallet'    =>  0,
            'is_customer'   =>  1,
            'symbol_name'   =>  $data['symbol_name'],
            'ware_house_id' =>  null,
            'is_active' =>  1,
            'address'  =>   $data['address'],
            'province'  =>  $data['province'],
            'district'  =>  $data['district'],
            'type_customer' =>  $data['type_customer'],
            'staff_sale_id' =>  $data['staff_sale_id']
        ];

        $user = User::firstOrCreate($data);

        DB::table('admin_role_users')->insert([
            'user_id'   =>  $user->id,
            'role_id'   =>  2
        ]);

        return redirect()->route('admin.login')->with('register', 'Đăng ký thành công');
    
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'symbol_name' => 'required|unique:admin_users,symbol_name',
            'username' => 'email|unique:admin_users,username',
            'password' => 'required|required_with:password_confirmation|same:password_confirmation',
            'mobile'    =>  'required'
        ], [
            'symbol_name.required'  =>  'Vui lòng nhập mã khách hàng.',
            'symbol_name.unique'    =>  'Mã khách hàng này đã tồn tại. Vui lòng nhập mã khách hàng khác.',
            'username.email'    =>  'Định dạng email không đúng. VD: abc@gmail.com.',
            'username.unique'   =>  'Email này đã được đăng ký. Vui lòng sử dụng email khác.',
            'mobile.required'   =>  'Vui lòng nhập số điện thoại.',
            'password.required' =>  'Vui lòng nhập mật khẩu.',
            'password.same' =>  'Mật khẩu xác nhận không đúng.'
        ]);
    }
}