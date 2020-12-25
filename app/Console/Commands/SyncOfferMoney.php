<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class SyncOfferMoney extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:offer';

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
        $orders = PurchaseOrder::where('final_payment', '>', 0)->get();

        foreach ($orders as $order) {
            echo $order->order_number . "\n";
            if ($order->items) {
                $total = 0;
                foreach ($order->items as $item) {
                    $total += $item->purchase_cn_transport_fee;
                }
            }

            $offer_cn = number_format($order->sumQtyRealityMoney() + $total - $order->final_payment, 2);
            $offer_vnd = round($offer_cn * $order->current_rate);

            $order->offer_cn = $offer_cn;
            $order->offer_vnd = $offer_vnd;
            $order->save();
        }
    }
}
