<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->unique()->after('name');
            $table->string('phone', 32)->nullable()->after('username');
            $table->foreignId('region_id')->nullable()->after('phone')->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('region_id')->constrained('states')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->after('state_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn(['username', 'phone', 'region_id', 'state_id', 'role_id']);
        });
    }
};
