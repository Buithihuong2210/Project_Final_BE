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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id'); // Khóa chính
            $table->unsignedBigInteger('order_id'); // Khóa ngoại trỏ tới bảng orders
            $table->string('transaction_no'); // Số giao dịch
            $table->string('bank_code')->nullable(); // Mã ngân hàng
            $table->string('card_type')->nullable(); // Loại thẻ
            $table->timestamp('pay_date')->nullable(); // Ngày thanh toán
            $table->string('status'); // Trạng thái thanh toán
            $table->timestamps();

            // Khóa ngoại với ON DELETE CASCADE
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
