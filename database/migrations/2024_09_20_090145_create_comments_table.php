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
        Schema::create('comments', function (Blueprint $table) {
            $table->id('comment_id'); // Comment ID
            $table->unsignedBigInteger('blog_id'); // Foreign key for blog
            $table->unsignedBigInteger('user_id'); // Foreign key for user
            $table->text('content'); // Comment content
            $table->timestamps(); // Created at and updated at timestamps

            // Foreign key constraints
            $table->foreign('blog_id')->references('blog_id')->on('blogs')->onDelete('cascade'); // Reference blog_id in blogs table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); // Reference id in users table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
