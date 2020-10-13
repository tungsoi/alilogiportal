<?php

use Illuminate\Routing\Router;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Route;

// ADMIN GROUP
Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'    =>  'admin.'
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('admin.home');

    $router->resources([
        'order_items'       =>  OrderItemController::class,
        'puchase_orders'    =>  PurchaseOrderController::class,
        'warehouses'    => WareHouseController::class
    ]);
});

// FRONTEND GROUP
Route::group([
    'prefix'        => config('admin.fe.route.prefix'),
    'namespace'     => config('admin.fe.route.namespace'),
    'middleware'    => config('admin.fe.route.middleware'),
], function (Router $router) {
    //
});