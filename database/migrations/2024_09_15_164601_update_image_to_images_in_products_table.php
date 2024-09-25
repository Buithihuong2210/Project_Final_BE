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
        Schema::table('products', function (Blueprint $table) {
            // Drop the existing 'image' column
            $table->dropColumn('image');

            // Add 'images' column to store JSON data
            $table->json('images')->nullable(); // Using JSON data type to store an array of image paths
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the 'images' column
            $table->dropColumn('images');

            // Add back the 'image' column
            $table->string('image')->nullable(); // Assuming 'image' was a single image path column
        });
    }
};
