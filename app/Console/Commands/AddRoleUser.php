<?php

namespace App\Console\Commands;

use App\Models\Alilogi\User as AlilogiUser;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddRoleUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:role';

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
        $users = AlilogiUser::select('id')->whereIsCustomer(1)->get();

        foreach ($users as $user) {
            DB::table('admin_role_users')->insert([
                'user_id'   =>  $user->id,
                'role_id'   =>  2
            ]);
        }
    }
}
