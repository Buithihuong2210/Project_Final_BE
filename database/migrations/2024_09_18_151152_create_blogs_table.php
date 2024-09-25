<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id('blog_id');  // Blog ID
            $table->string('title');  // Title of the blog
            $table->unsignedBigInteger('user_id');  // Foreign key for user
            $table->text('content');  // Blog content
            $table->string('thumbnail')->nullable();  // Image Logo
            $table->enum('status', ['draft', 'published'])->default('draft');  // Status (draft/published)
            $table->timestamps();  // Created at and updated at timestamps

            // Foreign key constraint
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
        Schema::dropIfExists('blogs');
    }
}
