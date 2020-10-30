<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->text('shop_name')->nullable()->comment('ten shop');
            $table->text('product_image')->nullable()->comment('link anh san pham. nhap tay: link trong storage, tool: link image online');
            $table->text('product_name')->nullable()->comment('ten san pham');
            $table->text('product_link')->nullable()->comment('link san pham');
            $table->string('product_id')->nullable()->comment('');
            $table->string('product_size')->nullable()->comment('size san pham');
            $table->string('product_color')->nullable()->comment('mau san pham');
            $table->string('property')->nullable()->comment('');
            $table->string('qty')->nullable()->comment('so luong mua');
            $table->string('qty_reality')->nullable()->comment('so luong mua thuc te');
            $table->string('price')->nullable()->comment('gia te');
            $table->text('customer_note')->nullable()->comment('ghi chu khach hang');
            $table->text('admin_note')->nullable()->comment('ghi chu admin');
            $table->string('price_range')->nullable()->comment('');
            $table->string('cn_code')->nullable()->comment('ma van don cua group van chuyen');
            $table->string('cn_order_number')->nullable()->comment('ma mua ho');
            $table->string('status')->nullable()->comment('trang thai');
            $table->string('current_rate')->nullable()->comment('');
            $table->string('transport_fee')->nullable()->comment('gia vcnd trung quoc');
            $table->string('weight')->nullable()->comment('can nang');
            $table->string('weight_date')->nullable()->comment('ngay vao can');
            $table->string('type')->nullable()->comment('');
            $table->text('internal_note')->nullable()->comment('ghi chu noi bo');
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
        Schema::dropIfExists('items');
    }
}
