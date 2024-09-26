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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id('voucher_id'); // Primary key
            $table->string('code')->unique(); // Voucher code
            $table->decimal('discount_amount', 8, 2); // Discount amount or percentage
            $table->enum('status', ['active', 'inactive']); // Voucher status
            $table->date('start_date'); // Start date of voucher validity
            $table->date('expiry_date'); // Expiry date of voucher validity
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
