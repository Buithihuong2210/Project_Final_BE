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
        Schema::create('blog_likes', function (Blueprint $table) {
            $table->id();  // Auto-incrementing ID
            $table->unsignedBigInteger('user_id');  // Foreign key for user
            $table->unsignedBigInteger('blog_id');  // Foreign key for blog
            $table->timestamps();  // Created at and updated at timestamps

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('blog_id')->references('blog_id')->on('blogs')->onDelete('cascade');

            // Unique constraint to prevent multiple likes by the same user on the same blog
            $table->unique(['user_id', 'blog_id']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_likes');
    }
};
