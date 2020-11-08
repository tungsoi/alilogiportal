<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use Illuminate\Console\Command;
use App\Models\Rongdo\PurchaseOrderItem;

class SyncPurchaseItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:item';

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
        $rongdo_items = PurchaseOrderItem::all();

        foreach ($rongdo_items as $rongdo_item) {
            OrderItem::create($rongdo_item->toArray());
        }
    }
}
