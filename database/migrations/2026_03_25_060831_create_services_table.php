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
        Schema::create('services', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->unsignedBigInteger('category_id');
        $table->string('location')->nullable();
        $table->text('description')->nullable();
        $table->string('image')->nullable();
        $table->boolean('feature_service')->default(0)->comment('0 = Inactive, 1 = Active');
        $table->boolean('status')->default(1)->comment('0 = Inactive, 1 = Active');
        $table->timestamps();
        $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
