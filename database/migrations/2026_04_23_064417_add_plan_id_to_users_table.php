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
        if (! Schema::hasColumn('users', 'plan_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('plan_id')->nullable()->after('category_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'plan_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('plan_id');
            });
        }
    }
};
