<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\Rongdo\Customer;
use Illuminate\Console\Command;
use App\Models\Rongdo\Order;
use App\User;

class SyncPurchaseOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $orders = Order::all();

        foreach ($orders as $order) {
            $data = $this->formatData($order->toArray());
            
            PurchaseOrder::insertGetId($data);
        }
    }

    public function formatData($data)
    {
        # code...
        $customer_id = $data['customer_id'];
        $customer = Customer::find($customer_id);
        $email = $customer->email;

        $ali_user = User::whereEmail($email)->first();
        $data['customer_id'] = $ali_user->id;
        $data['order_number'] = 'MH-'.$data['order_number'];
        $data['purchase_order_service_fee'] = (int) $data['purchase_service_fee'];
        $data['created_at'] = $data['created_at'];
        $data['updated_at'] = $data['updated_at'];
        return $data;
    }
}
