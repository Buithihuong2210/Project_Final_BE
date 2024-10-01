<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('responses', function (Blueprint $table) {
            $table->id('response_id');  // Primary Key - Response ID
            $table->unsignedBigInteger('question_id');  // Foreign Key - Question ID
            $table->unsignedBigInteger('user_id');  // Foreign Key - User ID (who answered)
            $table->text('answer_text')->nullable();  // Answer text (nullable in case of multiple choice)
            $table->timestamps();  // Created at and updated at timestamps

            // Foreign key constraint linking to questions table
            $table->foreign('question_id')->references('question_id')->on('questions')->onDelete('cascade');

            // Foreign key constraint linking to users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('responses');
    }
}
