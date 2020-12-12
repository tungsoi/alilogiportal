<?php

namespace App\Console\Commands;

use App\Models\Alilogi\TransportRecharge;
use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class SyncTypeRecharge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:type-recharge';

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
        $logs = TransportRecharge::all();

        $flag = 0;
        foreach ($logs as $log) {
            echo "$log->content \n";
            if (in_array($log->type_recharge, [TransportRecharge::PAYMENT, TransportRecharge::DEPOSITE_ORDER, TransportRecharge::PAYMENT_ORDER])) {
                // echo "$log->type_recharge \n";
                $log->type_recharge = TransportRecharge::DEDUCTION;
                $log->save();
            }
        }
    }
}
