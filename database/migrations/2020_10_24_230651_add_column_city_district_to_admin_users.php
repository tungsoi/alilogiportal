<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnCityDistrictToAdminUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_users', 'province_id')) {
                $table->integer('province_id')->nullable();
            }

            if (! Schema::hasColumn('admin_users', 'district_id')) {
                $table->integer('district_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (Schema::hasColumn('admin_users', 'province_id')) {
                $table->dropColumn('province_id');
            }

            if (Schema::hasColumn('admin_users', 'district_id')) {
                $table->dropColumn('district_id');
            }
        });
    }
}
