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
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id(); // Primary key for the pivot table
            $table->unsignedBigInteger('permission_id'); // Foreign key for permissions
            $table->unsignedBigInteger('role_id'); // Foreign key for roles
            $table->timestamps(); // Creates 'created_at' and 'updated_at' columns

            // Define foreign key constraints
            $table->foreign('permission_id')->references('permission_id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
