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
        Schema::table('users', function (Blueprint $table) {
            $table->string('image')->nullable()->after('password');
            $table->tinyInteger('type')->default(0)->comment('0 = User, 1 = Admin')->after('image');
             $table->string('phone')->nullable()->after('type');
             $table->string('role')->nullable()->after('phone');
             $table->string('status')->nullable()->comment('0 = Inactive, 1 = Active')->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
