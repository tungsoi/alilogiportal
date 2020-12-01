<?php

namespace App\Console\Commands;

use App\Models\Alilogi\TransportRecharge;
use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class SyncSuccessAt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:success';

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
        $orders = PurchaseOrder::whereStatus(PurchaseOrder::STATUS_SUCCESS)->get();
        foreach ($orders as $order) {
            echo $order->order_number . "\n";
            $log = TransportRecharge::whereTypeRecharge(TransportRecharge::PAYMENT_ORDER)
                    ->where('content', 'like', '%'.$order->order_number.'%')
                    ->first();

            if ($log) {
                $order->success_at = $log->created_at;
                $order->save();
            }
        }
    }
}
