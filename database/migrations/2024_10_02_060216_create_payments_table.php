<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id'); // Create auto-incrementing primary key for payment
            $table->unsignedInteger('order_id'); // Use unsignedInteger for compatibility with orders table
            $table->unsignedBigInteger('user_id');
            $table->string('payment_method')->default('MoMo');
            $table->decimal('amount', 10, 2); // Amount paid
            $table->string('status')->default('Pending'); // Payment status (Pending, Successful, Failed)
            $table->string('transaction_id')->nullable(); // MoMo transaction ID
            $table->timestamps();

            // Foreign key relationships
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
