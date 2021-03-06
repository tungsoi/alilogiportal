<?php

use Illuminate\Routing\Router;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ADMIN GROUP
Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'    =>  'admin.'
], function (Router $router) {

    Route::group([
        'prefix'        => 'auth',
        'as'    =>  'auth.'
    ], function (Router $router) {
        $router->resources([
            'users'       =>  UserController::class,
        ]);
    });

    $router->get('/', 'HomeController@index')->name('admin.home');

    $router->get('/customers/{id}/recharge', 'CustomerController@recharge')->name('customers.recharge');
    $router->post('/customers/recharge', 'CustomerController@rechargeStore')->name('customers.rechargeStore');
    $router->get('/customers/{id}/recharge-history', 'CustomerController@rechargeHistory')->name('customers.rechargeHistory');
    $router->get('/puchase_orders/{id}/deposite', 'PurchaseOrderController@deposite')->name('puchase_orders.deposite');
    $router->put('/puchase_orders/postDeposite', 'PurchaseOrderController@postDeposite')->name('puchase_orders.postDeposite');
    $router->put('/detail_orders/{order_id}/{item_id}', 'DetailOrderController@editable')->name('detail_orders.editable');
    $router->get('/carts/{item_id}/addCart', 'CartController@addCart')->name('carts.addCart');
    $router->get('/carts/{item_id}/addCart1688', 'CartController@addCart1688')->name('carts.addCart1688');
    $router->post('/carts/storeAddByTool', 'CartController@storeAddByTool')->name('carts.storeAddByTool');
    $router->post('/carts/storeAdd1688', 'CartController@storeAdd1688')->name('carts.storeAdd1688');
    $router->put('/customer_orders/{order_id}/{item_id}', 'CustomerOrderController@editable')->name('customer_orders.editable');
    $router->post('/offers/updateOrder', 'OfferController@updateOrder')->name('offers.updateOrder');
    $router->put('/offers/{id}', 'OfferController@editable')->name('offers.editable');
    $router->get('/customer_transport_orders', 'ProfileController@orderPayment')->name('profiles.historyOrder');
    $router->get('/customer_transport_orders/{id}', 'ProfileController@orderPaymentDetail')->name('profiles.orderPaymentDetail');
    $router->get('/transport_orders/{id}', 'TransportOrderController@show')->name('transport_orders.show');
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
        'schedule_logs' =>  ScheduleLogController::class,
        'detail_orders' =>  DetailOrderController::class,
        'complaints'    =>  ComplaintController::class,
        'offers'        =>  OfferController::class,
        'transactions'  =>  TransactionController::class
    ]);

    $router->get('/complaints/{complaint}', 'ComplaintController@showComplaint')->name('complaints.showComplaint');
    $router->post('/complaints/addComment', 'ComplaintController@addComment')->name('complaints.addComment');
    $router->post('/complaints/adminConfirmSuccess', 'ComplaintController@storeAdminConfirmSuccess')->name('complaints.storeAdminConfirmSuccess');
    $router->post('/complaints/customerConfirmSuccess', 'ComplaintController@storeCustomerConfirmSuccess')->name('complaints.storeCustomerConfirmSuccess');
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

Route::get('/my-account/orders/temps', 'App\\Admin\\Controllers\\ToolController@show')->name('orders.temps.show')->middleware(['web']);
Route::get('/my-account/orders/temps1688', 'App\\Admin\\Controllers\\ToolController@show1688')->name('orders.temps.show1688')->middleware(['web']);
Route::post('/my-account/orders/temps', 'App\\Admin\\Controllers\\ToolController@booking')->middleware(['web']);

Route::get('/demo-cors', function () {
    return view('cors');
});