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
            $table->uuid('uuid')->unique()->comment('Unique identifier untuk user');

            // User Identity Information
            $table->string('username', 50)->unique()->comment('Username untuk login');
            $table->string('full_name', 255)->comment('Nama lengkap user');
            $table->string('position', 100)->nullable()->comment('Posisi/jabatan user');
            $table->string('email', 255)->unique()->comment('Email user untuk login');
            $table->timestamp('email_verified_at')->nullable()->comment('Waktu verifikasi email');

            // Contact Information
            $table->string('phone_number', 20)->nullable()->comment('Nomor telepon user');
            $table->date('birth_date')->nullable()->comment('Tanggal lahir user');

            // Security & Authentication
            $table->string('password')->comment('Password terenkripsi');
            $table->rememberToken();

            // Password Management
            $table->timestamp('password_changed_at')->nullable()->comment('Waktu terakhir ubah password');
            $table->string('password_changed_by', 50)->nullable()->comment('system|admin_id|self');
            $table->unsignedInteger('password_change_count')->default(0)->comment('Jumlah total perubahan password');

            // Registration & Account Management
            $table->enum('registered_by', ['system', 'admin', 'self'])->default('self')->comment('Pendaftar: system, admin, atau self registration');
            $table->foreignId('registered_by_admin_id')->nullable()->constrained('users')->onDelete('set null')->comment('ID admin yang mendaftarkan');
            $table->text('registration_notes')->nullable()->comment('Catatan registrasi');

            // Login Tracking
            $table->timestamp('first_login_at')->nullable()->comment('Waktu login pertama kali');
            $table->timestamp('last_login_at')->nullable()->comment('Waktu login terakhir');
            $table->string('last_login_ip_private', 45)->nullable()->comment('IP private login terakhir');
            $table->string('last_login_ip_public', 45)->nullable()->comment('IP public login terakhir');
            $table->string('last_login_browser', 100)->nullable()->comment('Browser login terakhir');
            $table->string('last_login_browser_version', 20)->nullable()->comment('Versi browser login terakhir');
            $table->string('last_login_platform', 50)->nullable()->comment('Platform/OS login terakhir');
            $table->text('last_login_user_agent')->nullable()->comment('User agent login terakhir');
            $table->unsignedInteger('total_login_count')->default(0)->comment('Total jumlah login');

            // Current Session Information
            $table->string('current_ip_private', 45)->nullable()->comment('IP private sesi aktif');
            $table->string('current_ip_public', 45)->nullable()->comment('IP public sesi aktif');
            $table->string('current_browser', 100)->nullable()->comment('Browser sesi aktif');
            $table->string('current_browser_version', 20)->nullable()->comment('Versi browser sesi aktif');
            $table->string('current_platform', 50)->nullable()->comment('Platform/OS sesi aktif');
            $table->text('current_user_agent')->nullable()->comment('User agent sesi aktif');

            // Profile & Media
            $table->string('avatar')->nullable()->comment('Path avatar/foto profil user');

            // Security & Blocking System (Enhanced)
            $table->integer('failed_login_attempts')->default(0)->comment('Jumlah percobaan login gagal berturut-turut');
            $table->enum('account_status', ['active', 'blocked', 'suspended', 'terminated'])->default('active')
                ->comment('Status akun: active=aktif, blocked=diblokir sistem (3x gagal), suspended=dibekukan admin, terminated=dihentikan admin');
            $table->boolean('is_active')->default(true)->comment('Status aktif user (true/false)');
            $table->boolean('is_locked')->default(false)->comment('Status kunci manual oleh admin');
            $table->timestamp('locked_at')->nullable()->comment('Waktu akun dikunci/diblokir');
            $table->string('locked_by', 50)->nullable()->comment('Siapa yang mengunci: system|admin_id');
            $table->text('locked_reason')->nullable()->comment('Alasan akun dikunci/diblokir/suspended/terminated');
            $table->foreignId('blocked_by')->nullable()->constrained('users')->onDelete('set null')->comment('ID admin yang memblokir');
            $table->timestamp('blocked_until')->nullable()->comment('Waktu berakhir pemblokiran (untuk temporary block)');

            // Timestamps
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete untuk audit trail');

            // Indexes for Performance
            $table->index('uuid');
            $table->index('username');
            $table->index('email');
            $table->index('phone_number');
            $table->index('registered_by');
            $table->index('first_login_at');
            $table->index('last_login_at');
            $table->index('account_status');
            $table->index('is_active');
            $table->index('is_locked');
            $table->index('birth_date');
            $table->index('locked_at');
            $table->index('blocked_until');
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Activity Information
            $table->string('activity_type', 100)->comment('login|logout|password_change|profile_update|account_blocked|account_suspended|etc');
            $table->text('activity_description')->nullable()->comment('Deskripsi detail aktivitas');
            $table->json('activity_data')->nullable()->comment('Data tambahan dalam format JSON (metadata aktivitas)');

            // Request Information
            $table->string('ip_address_private', 45)->nullable()->comment('IP address private');
            $table->string('ip_address_public', 45)->nullable()->comment('IP address public');
            $table->string('browser', 100)->nullable()->comment('Nama browser');
            $table->string('browser_version', 20)->nullable()->comment('Versi browser');
            $table->string('platform', 50)->nullable()->comment('Platform/OS');
            $table->text('user_agent')->nullable()->comment('Full user agent string');

            // Additional Context
            $table->string('method', 10)->nullable()->comment('HTTP Method: GET|POST|PUT|DELETE|etc');
            $table->string('url', 500)->nullable()->comment('URL yang diakses');
            $table->string('referrer', 500)->nullable()->comment('URL referrer');
            $table->unsignedSmallInteger('status_code')->nullable()->comment('HTTP status code response');

            // Session Information
            $table->string('session_id', 100)->nullable()->comment('Session ID terkait');

            // Audit Information
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null')->comment('ID user yang melakukan (untuk aksi admin)');
            $table->enum('action_result', ['success', 'failed', 'error'])->default('success')->comment('Hasil dari aktivitas');
            $table->text('error_message')->nullable()->comment('Pesan error jika action_result = failed/error');

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
            $table->index('action_result');
            $table->index('performed_by');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index()->comment('Email user yang request reset password');
            $table->string('token')->comment('Token reset password');
            $table->string('ip_address_private', 45)->nullable()->comment('IP private yang request reset');
            $table->string('ip_address_public', 45)->nullable()->comment('IP public yang request reset');
            $table->text('user_agent')->nullable()->comment('User agent yang request reset');
            $table->timestamp('created_at')->nullable()->comment('Waktu request dibuat');
            $table->timestamp('expires_at')->nullable()->comment('Waktu token kadaluarsa');
            $table->timestamp('used_at')->nullable()->comment('Waktu token digunakan');
            $table->string('used_ip', 45)->nullable()->comment('IP yang menggunakan token');
            $table->boolean('is_used')->default(false)->comment('Status apakah token sudah digunakan');

            // Indexes
            $table->index(['email', 'token']);
            $table->index('created_at');
            $table->index('expires_at');
            $table->index('is_used');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();

            // IP & Device Information
            $table->string('ip_address_private', 45)->nullable()->comment('IP address private');
            $table->string('ip_address_public', 45)->nullable()->comment('IP address public');
            $table->string('browser', 100)->nullable()->comment('Nama browser');
            $table->string('browser_version', 20)->nullable()->comment('Versi browser');
            $table->string('platform', 50)->nullable()->comment('Platform/OS');
            $table->text('user_agent')->nullable()->comment('Full user agent string');

            // Session Data
            $table->longText('payload');
            $table->integer('last_activity')->index();

            // Session Tracking
            $table->timestamp('created_at')->nullable()->comment('Waktu session dibuat');
            $table->timestamp('expires_at')->nullable()->comment('Waktu session kadaluarsa');
            $table->boolean('is_active')->default(true)->comment('Status session aktif');

            // Additional Indexes
            $table->index('ip_address_public');
            $table->index('is_active');
            $table->index('expires_at');
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
