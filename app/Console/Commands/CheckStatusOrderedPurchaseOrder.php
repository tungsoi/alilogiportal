<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use Illuminate\Console\Command;
use App\Models\PurchaseOrder;
use App\Models\ScheduleLog;

class CheckStatusOrderedPurchaseOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:status-ordered';

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

        $codes = [];
        foreach ($orders as $order) {
            $items = $order->items;

            foreach ($items as $item) {
                if ($item->status == OrderItem::STATUS_PURCHASE_WAREHOUSE_VN) {
                    $codes[$order->order_number] = $order->order_number;
                }
            }
        }

        if (sizeof($codes)) {
            foreach ($codes as $code) {
                PurchaseOrder::where('order_number', $code)->update([
                    'status'    =>  PurchaseOrder::STATUS_IN_WAREHOUSE_VN
                ]);
            }
        }

        
        ScheduleLog::create([
            'code'  =>  'Check trạng thái đơn hàng đã đặt hàng, nhưng vẫn có sản phẩm đã về việt nam, đổi trạng thái đơn sang đã về kho VN. Số đơn hàng ' .sizeof($codes)
        ]);
    }
}
