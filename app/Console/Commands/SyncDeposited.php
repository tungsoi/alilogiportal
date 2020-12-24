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
        $orders = PurchaseOrder::all();

        foreach ($orders as $order) {
            if ($order->deposited_at != null && strlen($order->deposited_at) == 10) {
                echo $order->order_number."\n";
                $order->deposited_at = $order->deposited_at. " 00:00:00";
                $order->save();
            }
        }
    }
}
