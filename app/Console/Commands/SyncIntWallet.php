<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class SyncIntWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:wallet';

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
        $users = User::whereIsCustomer(1)->get();

        foreach ($users as $user)
        {
            echo $user->symbol_name . "\n";
            $wallet = $user->wallet;
            $round = (int) round($wallet);
            
            $user->wallet = $round;
            $user->save();
        }
    }
}
