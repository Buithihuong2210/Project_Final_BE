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
        // Thêm cột 'guard_name' vào bảng 'roles'
        Schema::table('roles', function (Blueprint $table) {
            $table->string('guard_name')->default('web'); // Bạn có thể đặt giá trị mặc định là 'web'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa cột 'guard_name' khi rollback migration
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('guard_name');
        });
    }
};
