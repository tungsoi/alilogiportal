<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderRechargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_recharges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('customer_id')->nullable();
            $table->integer('user_id_created')->nullable();
            $table->string('money')->nullable();
            $table->integer('type_recharge')->default(1)
            ->comment("0   =>  'Nạp tiền mặt',
                1   =>  'Nạp tiền chuyển khoản',
                2   =>  'Trừ tiền'");
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_recharges');
    }
}
