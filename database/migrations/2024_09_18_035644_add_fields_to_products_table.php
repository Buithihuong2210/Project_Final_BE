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
            $table->string('short_description')->nullable()->after('description'); // Thêm cột 'short_description'
            $table->decimal('volume', 8, 2)->nullable()->after('price'); // Thêm cột 'volume' (dung tích), số thập phân
            $table->string('nature')->nullable()->after('volume'); // Thêm cột 'nature' (loại sản phẩm)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('short_description');
            $table->dropColumn('volume');
            $table->dropColumn('nature');
        });
    }
};
