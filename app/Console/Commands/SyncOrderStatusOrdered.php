<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class SyncOrderStatusOrdered extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:order-ordered';

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
        $orders = PurchaseOrder::whereStatus(PurchaseOrder::STATUS_ORDERED)->get();

        foreach ($orders as $order) {
            echo "$order->order_number \n";

            $flag = false;

            foreach ($order->items as $item) {
                if ($item->status == OrderItem::STATUS_PURCHASE_WAREHOUSE_VN) {
                    $flag = true;
                }
            }

            if ($flag) {
                $order->status = PurchaseOrder::STATUS_IN_WAREHOUSE_VN;
                $order->save();
            }
        }
    }
}
