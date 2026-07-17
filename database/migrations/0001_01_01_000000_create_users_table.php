<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['vendor', 'buyer', 'admin'])->default('buyer');
            $table->string('phone')->nullable();
            $table->enum('identity_status', ['not_submitted', 'pending', 'approved', 'rejected'])->default('not_submitted');
            $table->decimal('reputation_score', 3, 2)->default(0.00);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
