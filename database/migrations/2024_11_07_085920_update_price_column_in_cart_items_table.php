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
        Schema::table('cart_items', function (Blueprint $table) {
            // Thay đổi cột 'price' thành decimal(65, 2)
            $table->decimal('price', 65, 2)->change();  // Dùng phương thức `change()` để sửa cột đã tồn tại
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Quay lại cột 'price' kiểu decimal(8, 2) nếu cần thiết
            $table->decimal('price', 8, 2)->change();  // Quay về kiểu cũ
        });
    }
};
