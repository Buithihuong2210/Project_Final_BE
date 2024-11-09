<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('guard_name')->default('web'); // Thêm cột guard_name
        });
    }

    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('guard_name'); // Xóa cột guard_name khi rollback
        });
    }
};
