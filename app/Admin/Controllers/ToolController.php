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
        $data = $request->data['selected'];
        return $data;
    }

    public function show(Request $request)
    {
        # code...

        dd($request->all());
    }
}
