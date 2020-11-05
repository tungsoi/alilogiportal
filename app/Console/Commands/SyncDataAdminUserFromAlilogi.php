<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alilogi\User as AlilogiUser;
use App\Models\ScheduleLog;
use App\User as AloorderUser;
use Illuminate\Support\Facades\DB;

class SyncDataAdminUserFromAlilogi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:user-alilogi';

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
        $aliUsers = AlilogiUser::all();
        $aloUsers = AloorderUser::all();

        foreach ($aliUsers as $aliUser) {
            echo "$aliUser->username \t";
            $flag = AloorderUser::whereUsername($aliUser->username)->first();

            if ($flag) {
                AloorderUser::find($aliUser->id)->update($aliUser->toArray());
                echo " update \n";
            } else {
                AloorderUser::insertGetId($aliUser->toArray());
                echo " create \n";

                DB::table('admin_role_users')->insert([
                    'user_id'   =>  $aliUser->id,
                    'role_id'   =>  2
                ]);
            }
        }

        ScheduleLog::create([
            'code'  =>  'sync-user-from-logi-to-order'
        ]);
    }
}
