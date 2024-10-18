<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveriesTable extends Migration
{
    public function up()
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id'); // Khóa ngoại trỏ tới bảng orders
            $table->string('status'); // Trạng thái giao hàng
            $table->string('tracking_number')->nullable(); // Số theo dõi (nếu có)
            $table->timestamp('delivered_at')->nullable(); // Thời gian giao hàng
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');

        });
    }

    public function down()
    {
        Schema::dropIfExists('deliveries');
    }
}
