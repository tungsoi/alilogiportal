<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolController extends Controller
{
    public function booking(Request $request)
    {
        $data = $request->all();
        $order = $data['data']['selected'];
        dd($order);
        $request->session()->push('booking_product', $order);

        return redirect()->route('admin.carts.create');
    }
}
