<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_key')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('subject');
            $table->longText('body')->nullable();
            $table->string('heading')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
