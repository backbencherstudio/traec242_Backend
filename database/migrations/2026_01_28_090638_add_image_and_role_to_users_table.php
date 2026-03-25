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
            $table->tinyInteger('type')->default(0)->comment('0 = User, 1 = Admin, 2 = Provider' )->after('image');
             $table->string('phone')->nullable()->after('type');
             $table->string('role')->nullable()->after('phone');
             $table->string('last_name')->nullable()->after('name');
             $table->string('address')->nullable()->after('role');
             $table->string('city')->nullable()->after('address');
             $table->string('state')->nullable()->after('city');
             $table->string('zip_code')->nullable()->after('state');
             $table->string('bio')->nullable()->after('zip_code');
             $table->json('languages')->nullable()->after('bio');
             $table->json('category_id')->nullable()->after('languages');
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
