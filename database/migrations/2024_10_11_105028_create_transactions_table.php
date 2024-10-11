<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // Tạo id tự động tăng
            $table->unsignedBigInteger('order_id'); // ID đơn hàng
            $table->string('transaction_no'); // Số giao dịch
            $table->string('bank_code'); // Mã ngân hàng
            $table->string('card_type'); // Loại thẻ
            $table->timestamp('pay_date'); // Ngày thanh toán
            $table->string('status'); // Trạng thái giao dịch
            $table->timestamps(); // Thời gian tạo và cập nhật
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
