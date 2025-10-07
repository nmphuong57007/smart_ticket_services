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
            // Drop existing columns that are different
            $table->dropColumn(['name', 'email_verified_at', 'remember_token']);
            
            // Add new columns according to cinema booking schema
            $table->string('fullname', 100)->nullable()->after('id');
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('password');
            $table->enum('role', ['customer', 'staff', 'admin'])->default('customer')->after('avatar');
            $table->integer('points')->default(0)->after('role');
            $table->enum('status', ['active', 'blocked'])->default('active')->after('points');
            
            // Modify existing columns
            $table->string('email', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore original columns
            $table->string('name')->after('id');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->rememberToken()->after('password');
            
            // Drop added columns
            $table->dropColumn(['fullname', 'phone', 'avatar', 'role', 'points', 'status']);
            
            // Restore original email length
            $table->string('email', 191)->change();
        });
    }
};
