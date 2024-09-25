<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->integer('user_id');
            $table->decimal('total_amount', 10, 2);
            $table->text('shipping_address');
            $table->string('order_status');
            $table->string('payment_status');
            $table->integer('shipping_id');
            $table->decimal('shipping_cost', 10, 2);
            $table->timestamp('order_date')->useCurrent();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
