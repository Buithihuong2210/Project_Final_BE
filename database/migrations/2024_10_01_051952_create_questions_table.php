<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id('question_id');  // Primary Key - Question ID
            $table->unsignedBigInteger('survey_id');  // Foreign Key - Survey ID
            $table->text('question_text');  // Content of the question
            $table->enum('question_type', ['multiple_choice', 'text'])->default('multiple_choice');  // Type of the question
            $table->timestamps();  // Created at and updated at timestamps

            // Foreign key constraint linking to surveys table
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
        Schema::dropIfExists('questions');
    }
}
