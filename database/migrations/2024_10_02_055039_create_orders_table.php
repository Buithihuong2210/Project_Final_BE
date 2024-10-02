<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('order_id');
            $table->unsignedBigInteger('user_id'); // Store user ID without foreign key
            $table->unsignedBigInteger('shipping_id');
            $table->unsignedBigInteger('voucher_id')->nullable(); // Voucher ID without foreign key
            $table->string('shipping_name'); // Name of the shipping method
            $table->decimal('shipping_cost', 8, 2); // Shipping fee
            $table->string('shipping_address'); // Shipping address
            $table->decimal('subtotal_of_cart', 10, 2); // Total cart value
            $table->decimal('total_amount', 10, 2); // Total payment amount
            $table->enum('status', ['Processing', 'Shipping', 'Delivered', 'Completed'])->default('Processing');
            $table->enum('payment_method', ['Cash on Delivery', 'VNpay Payment']); // Enum for payment methods
            $table->timestamp('order_date')->useCurrent(); // Order date
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
