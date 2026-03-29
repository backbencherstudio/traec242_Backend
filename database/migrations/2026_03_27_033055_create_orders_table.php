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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_pricing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('event_name');
            $table->integer('guest_count')->nullable();
            $table->string('event_duration')->nullable();
            $table->text('event_description')->nullable();
            $table->date('event_start_date');
            $table->date('event_end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('question_one')->nullable();
            $table->string('question_two')->nullable();
            $table->string('question_three')->nullable();
            $table->string('question_four')->nullable();
            $table->string('question_five')->nullable();
            $table->string('question_six')->nullable();
            $table->foreignId('include_order_id')->constrained()->cascadeOnDelete();
            $table->boolean('agree_terms')->default(false);
            $table->enum('payment_method', ['stripe'])->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
