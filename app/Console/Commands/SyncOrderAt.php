<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class SyncOrderAt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:order_at';

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
        $orders = PurchaseOrder::whereStatus(5)->get();

        foreach ($orders as $order) 
        {
            if ($order->deposited_at == '2020-11-19') {
                $order->order_at = str_replace('24', '19', $order->order_at);
                $order->save();
            }
            
        }
    }
}
