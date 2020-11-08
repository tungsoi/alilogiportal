<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnUserConfirmOrderdIdToPurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'user_id_confirm_ordered')) {
                $table->integer('user_id_confirm_ordered')->nullable()->comment('Id Admin xác nhận đã đặt hàng đơn hàng này.');
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
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'user_id_confirm_ordered')) {
                $table->dropColumn('user_id_confirm_ordered');
            }
        });
    }
}
