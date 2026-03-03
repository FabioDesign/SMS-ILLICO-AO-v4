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
            $table->id();
            $table->uuid('uid');
            $table->string('lastname', 255);
            $table->string('firstname', 255);
            $table->string('number', 20)->unique();
            $table->string('email', 255)->unique();
            $table->char('codepin', 4)->default('7070');
            $table->string('company', 255)->nullable();
            $table->string('nif', 50)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->decimal('volume', 10, 0)->default('0');
            $table->char('lg', 2);
            $table->string('avatar', 20)->nullable();
            $table->timestamp('avatar_at')->nullable();
            $table->string('password', 255)->nullable();
            $table->timestamp('password_at')->nullable();
            $table->string('otp', 6)->nullable();
            $table->timestamp('otp_at')->nullable();
            $table->timestamp('login_at')->nullable();
            $table->tinyInteger('status')->default('0');
            $table->timestamps();
            $table->integer('town_id');
            $table->integer('accountyp_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
