<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy các migration.
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Xóa cột 'images'
            $table->dropColumn('images');
            // Thêm cột 'image' với kiểu dữ liệu text và cho phép null
            $table->text('image')->nullable();
        });
    }

    /**
     * Đảo ngược các migration.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Xóa cột 'image'
            $table->dropColumn('image');
            // Thêm lại cột 'images'
            $table->text('images')->nullable();
        });
    }
};
