<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\ScheduleLog;
use Illuminate\Console\Command;

class SyncPurchaseOrderServiceFee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:service_fee';

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
        $orders = PurchaseOrder::whereNull('purchase_order_service_fee')
        ->where('status', '!=', PurchaseOrder::STATUS_CANCEL)
        ->where('status', '!=', PurchaseOrder::STATUS_SUCCESS)
        ->orderBy('id', 'desc')->get();

        foreach ($orders as $order) {
            echo $order->order_number . "\n";
            $customer_percent_service = (int) $order->customer->customer_percent_service;

            if ($customer_percent_service == 0 && ! in_array($order->customer->id, [1043, 1389, 1020])) {
                $customer_percent_service = 1;
            }
            $purchase_total_items_price = $order->sumQtyRealityMoney();

            $purchase_order_service_fee = number_format($purchase_total_items_price / 100 * $customer_percent_service, 2);

            $order->purchase_order_service_fee = $purchase_order_service_fee;
            $order->save();
        }

        ScheduleLog::create([
            'code'  =>  'Tính phí dịch vụ những đơn hàng thiếu dữ liệu. Số đơn hàng ' .$orders->count()
        ]);
    }
}
