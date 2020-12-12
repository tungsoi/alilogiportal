<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class SyncTotalMoneyPurchaseOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchase-order:sync-total-money';

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
        $orders = PurchaseOrder::where('status', '!=', PurchaseOrder::STATUS_SUCCESS)->orderBy('id', 'desc')->get();

        foreach ($orders as $order) {
            $items_price = $order->purchase_total_items_price;
            $items_price_reality = $order->sumQtyRealityMoney();

            if ($items_price !== $items_price_reality) {
                dd($order->order_number . " - database: $items_price - reality: $items_price_reality" );
            }
        }
    }
}
