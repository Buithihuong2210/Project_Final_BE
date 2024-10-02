<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSurveyIdToResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_id')->after('user_id'); // Thêm trường survey_id

            // Ràng buộc khóa ngoại liên kết đến bảng surveys
            $table->foreign('survey_id')->references('survey_id')->on('surveys')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropForeign(['survey_id']); // Xóa ràng buộc khóa ngoại
            $table->dropColumn('survey_id'); // Xóa trường survey_id
        });
    }
}