<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\Rongdo\Order;
use Illuminate\Console\Command;

class SyncDeposited extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:deposited';

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
        $orders = Order::whereOrderType(1)->get();

        $data = [];
        foreach ($orders as $order) {
            $new_order = PurchaseOrder::whereOrderNumber('MH-'.$order->order_number)->first();
            if ($new_order && $order->deposited != "" && round($new_order->deposited) != round($order->deposited)) {
                $data['MH-'.$order->order_number] = [
                    'old'   =>  $order->deposited,
                    'new'   =>  $new_order->deposited
                ];
            }
        }

        dd($data);
    }
}
