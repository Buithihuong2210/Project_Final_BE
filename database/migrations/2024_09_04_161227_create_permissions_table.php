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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id('permission_id'); // Creates 'permission_id' column as the primary key
            $table->unsignedBigInteger('role_id'); // Foreign key column for roles
            $table->string('permission'); // Column to store permission details
            $table->timestamps(); // Creates 'created_at' and 'updated_at' columns

            // Define foreign key constraint
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
