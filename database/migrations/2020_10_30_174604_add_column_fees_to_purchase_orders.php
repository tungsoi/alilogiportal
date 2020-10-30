<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnFeesToPurchaseOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'purchase_order_service_fee')) {
                $table->string('purchase_order_service_fee')->default(0)->comment('Phi dich vu');
            }

            if (! Schema::hasColumn('purchase_orders', 'purchase_order_transport_fee')) {
                $table->string('purchase_order_transport_fee')->default(0)->comment('Phi van chuyen');
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
            if (Schema::hasColumn('purchase_orders', 'purchase_order_service_fee')) {
                $table->dropColumn('purchase_order_service_fee');
            }

            if (Schema::hasColumn('purchase_orders', 'purchase_order_transport_fee')) {
                $table->dropColumn('purchase_order_transport_fee');
            }
        });
    }
}
