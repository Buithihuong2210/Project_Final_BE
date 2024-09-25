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
        Schema::create('brands', function (Blueprint $table) {
            $table->id('brand_id'); // Creates 'brand_id' column as the primary key
            $table->string('name'); // Column to store brand name
            $table->text('description')->nullable(); // Column to store brand description, allowing null values
            $table->string('image')->nullable(); // Column to store image URL or path, allowing null values
            $table->unsignedInteger('total_products')->default(0); // Column to track the total number of products in the brand
            $table->timestamps(); // Creates 'created_at' and 'updated_at' columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
