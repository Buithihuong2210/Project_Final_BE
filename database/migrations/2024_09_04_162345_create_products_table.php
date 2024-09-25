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
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id'); // Creates 'product_id' column as the primary key
            $table->string('name'); // Column to store the product name
            $table->text('description')->nullable(); // Column to store the product description, allows null values
            $table->decimal('price', 10, 2); // Column to store the product price with 2 decimal places
            $table->unsignedInteger('quantity'); // Column to store the product quantity
            $table->unsignedBigInteger('brand_id'); // Foreign key column for brands
            $table->string('image')->nullable(); // Column to store the image path, allows null values
            $table->string('status'); // Column to store the product status
            $table->timestamps(); // Creates 'created_at' and 'updated_at' columns

            // Define foreign key constraint
//            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
