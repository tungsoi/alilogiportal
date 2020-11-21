<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Alilogi\Province;
use App\Models\Alilogi\District;
use App\User;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        # code...
        return view('frontend.index');
    }

    public function register()
    {
        # code...  

        $provinces = Province::all();
        $districts = District::all();


        $sales = DB::connection('aloorder')->table('admin_role_users')->where('role_id', 3)->get()->pluck('user_id');
        $saleStaff = User::whereIn('id', $sales)->get()->pluck('name', 'id');

        return view('frontend.register', compact('provinces', 'districts', 'saleStaff'));
    }
}