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
        Schema::create('users', function (Blueprint $table) {
            // Primary Keys
            $table->id();
            $table->uuid('uuid')->unique();

            // User Identity Information
            $table->string('username', 50)->unique();
            $table->string('full_name', 255);
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();

            // Contact Information
            $table->string('phone_number', 20)->nullable();
            $table->date('birth_date')->nullable();

            // Security & Authentication
            $table->string('password');
            $table->rememberToken();

            // Password Management
            $table->timestamp('password_changed_at')->nullable();
            $table->string('password_changed_by', 50)->nullable()->comment('system|admin_id|self');
            $table->unsignedInteger('password_change_count')->default(0);

            // Registration & Account Management
            $table->enum('registered_by', ['system', 'admin', 'self'])->default('self');
            $table->foreignId('registered_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('registration_notes')->nullable();

            // Login Tracking
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip_private', 45)->nullable();
            $table->string('last_login_ip_public', 45)->nullable();
            $table->string('last_login_browser', 100)->nullable();
            $table->string('last_login_browser_version', 20)->nullable();
            $table->string('last_login_platform', 50)->nullable();
            $table->text('last_login_user_agent')->nullable();
            $table->unsignedInteger('total_login_count')->default(0);

            // Current Session Information
            $table->string('current_ip_private', 45)->nullable();
            $table->string('current_ip_public', 45)->nullable();
            $table->string('current_browser', 100)->nullable();
            $table->string('current_browser_version', 20)->nullable();
            $table->string('current_platform', 50)->nullable();
            $table->text('current_user_agent')->nullable();

            // Profile & Media
            $table->string('avatar')->nullable()->comment('Profile picture path');

            // Account Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->string('locked_by', 50)->nullable();
            $table->text('locked_reason')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for Performance
            $table->index('uuid');
            $table->index('username');
            $table->index('email');
            $table->index('phone_number');
            $table->index('registered_by');
            $table->index('last_login_at');
            $table->index('is_active');
            $table->index('is_locked');
            $table->index('birth_date');
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Activity Information
            $table->string('activity_type', 100)->comment('login|logout|password_change|profile_update|etc');
            $table->text('activity_description')->nullable();
            $table->json('activity_data')->nullable()->comment('Additional activity metadata');

            // Request Information
            $table->string('ip_address_private', 45)->nullable();
            $table->string('ip_address_public', 45)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('browser_version', 20)->nullable();
            $table->string('platform', 50)->nullable();
            $table->text('user_agent')->nullable();

            // Additional Context
            $table->string('method', 10)->nullable()->comment('GET|POST|PUT|DELETE|etc');
            $table->string('url', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();

            // Session Information
            $table->string('session_id', 100)->nullable();

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexes for Performance
            $table->index('user_id');
            $table->index('activity_type');
            $table->index('created_at');
            $table->index(['user_id', 'activity_type']);
            $table->index(['user_id', 'created_at']);
            $table->index('ip_address_public');
            $table->index('session_id');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token');
            $table->string('ip_address_private', 45)->nullable();
            $table->string('ip_address_public', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();

            // Indexes
            $table->index(['email', 'token']);
            $table->index('created_at');
            $table->index('expires_at');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();

            // IP & Device Information
            $table->string('ip_address_private', 45)->nullable();
            $table->string('ip_address_public', 45)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('browser_version', 20)->nullable();
            $table->string('platform', 50)->nullable();
            $table->text('user_agent')->nullable();

            // Session Data
            $table->longText('payload');
            $table->integer('last_activity')->index();

            // Additional Indexes (user_id sudah di-index oleh foreignId di atas)
            $table->index('ip_address_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('users');
    }
};
