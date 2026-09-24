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
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'name') && ! Schema::hasColumn('users', 'first_name')) {
                $table->renameColumn('name', 'first_name');
            }

            $columnsToDrop = [];
            foreach (['jwt_token', 'role', 'is_verified', 'provider_status', 'remember_token'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'first_name') && ! Schema::hasColumn('users', 'name')) {
                $table->renameColumn('first_name', 'name');
            }

            $table->rememberToken();
            $table->string('jwt_token', 1000)->nullable();
            $table->string('role')->nullable();
            $table->boolean('provider_status')->nullable()->default(false);
            $table->boolean('is_verified')->default(false);
        });
    }
};
