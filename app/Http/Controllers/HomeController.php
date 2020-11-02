<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Alilogi\Province;
use App\Models\Alilogi\District;
use Encore\Admin\Facades\Admin;

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

        return view('frontend.register', compact('provinces', 'districts'));
    }
}