<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComplaintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_number')->nullable()->comment('ma don hang order');
            $table->string('cn_code')->nullable();
            $table->string('image')->nullable();
            $table->text('reason')->nullable();
            $table->text('solution')->nullable();
            $table->integer('sale_staff_id')->nullable();
            $table->integer('order_staff_id')->nullable();
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
        Schema::dropIfExists('complaints');
    }
}
