<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveriesTable extends Migration
{
    public function up()
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id('delivery_id');
            $table->unsignedBigInteger('order_id'); // Khóa ngoại trỏ tới bảng orders
            $table->string('delivery_address');
            $table->dateTime('delivery_date');
            $table->string('status')->default('pending'); // Trạng thái giao hàng
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');

        });

    }

    public function down()
    {
        Schema::dropIfExists('deliveries');
    }
}
