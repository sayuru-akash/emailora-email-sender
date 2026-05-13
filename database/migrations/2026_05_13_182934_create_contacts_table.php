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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email');
            $table->string('email_normalized')->unique();
            $table->string('phone')->nullable();
            $table->string('company')->nullable()->index();
            $table->string('job_title')->nullable();
            $table->string('country')->nullable()->index();
            $table->string('district')->nullable()->index();
            $table->string('city')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('source')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->string('email_verified_status')->default('unknown')->index();
            $table->string('consent_status')->default('unknown')->index();
            $table->string('consent_source')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable()->index();
            $table->timestamp('last_opened_at')->nullable()->index();
            $table->timestamp('last_clicked_at')->nullable()->index();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['country', 'district', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
