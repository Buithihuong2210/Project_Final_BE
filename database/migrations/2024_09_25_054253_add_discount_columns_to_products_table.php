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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('discount', 5, 2)->nullable()->after('price'); // Thêm cột 'discount'
            $table->decimal('discounted_price', 10, 2)->nullable()->after('discount'); // Thêm cột 'discounted_price'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('discount');
            $table->dropColumn('discounted_price');
        });
    }
};
