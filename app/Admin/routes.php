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

    $router->get('/customers/{id}/recharge', 'CustomerController@recharge')->name('customers.recharge');
    $router->post('/customers/recharge', 'CustomerController@rechargeStore')->name('customers.rechargeStore');
    $router->get('/customers/{id}/recharge-history', 'CustomerController@rechargeHistory')->name('customers.rechargeHistory');

    $router->resources([
        'order_items'       =>  OrderItemController::class,
        'puchase_orders'    =>  PurchaseOrderController::class,
        'warehouses'        =>  WareHouseController::class,
        'exchange_rates'    =>  ExchangeRateController::class,
        'customers'         =>  CustomerController::class ,
        'carts'             =>  CartController::class,
        'customer_items'    =>  CustomerItemController::class,
        'customer_recharges'    =>  CustomerRechargeController::class,
        'customer_orders'   =>  CustomerOrderController::class,
        'schedule_logs' =>  ScheduleLogController::class
    ]);
});


// HOME GROUP
Route::group([
    'prefix'        =>  'customer',
    'namespace'     => 'App\\Admin\\Controllers\\Customer',
    'as'    =>  'customer.',
    'middleware' => ['web']
], function (Router $router) {
    $router->post('register', 'RegisterController@postRegister')->name('postRegister');
});
