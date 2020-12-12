<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use Illuminate\Support\Facades\DB;

class SyncRoleCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:role-user';

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
        $customers = User::whereIsCustomer(1)->get();

        foreach ($customers as $customer) {
            echo $customer->symbol_name . "\n";
            $flag_alilogi = DB::connection('alilogi')->table('admin_role_users')
                ->where('role_id', 2)
                ->where('user_id', $customer->id)
                ->get();

            if (! $flag_alilogi) {
                DB::connection('alilogi')->table('admin_role_users')
                ->create([
                    'role_id'   =>  2,
                    'user_id'   =>  $customer->id
                ]);
            }

            $flag_aloorder = DB::connection('aloorder')->table('admin_role_users')
                ->where('role_id', 2)
                ->where('user_id', $customer->id)
                ->get();

            if (! $flag_aloorder) {
                DB::connection('alilogi')->table('admin_role_users')
                ->create([
                    'role_id'   =>  2,
                    'user_id'   =>  $customer->id
                ]);
            }
        }
    }
}
