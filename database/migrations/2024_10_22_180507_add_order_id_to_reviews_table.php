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
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('product_id'); // Thêm trường order_id
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('set null'); // Ràng buộc khóa ngoại
        });
    }

    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']); // Xóa ràng buộc khóa ngoại
            $table->dropColumn('order_id'); // Xóa trường order_id
        });
    }
};
