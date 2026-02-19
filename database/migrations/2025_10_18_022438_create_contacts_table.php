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
            $table->uuid('uid');
            $table->string('label', 255);
            $table->string('number', 20);
            $table->string('gender', 1)->nullable();
            $table->date('date_at')->nullable();
            $table->string('field1', 255)->nullable();
            $table->string('field2', 255)->nullable();
            $table->string('field3', 255)->nullable();
            $table->tinyInteger('blacklist')->default('0');
            $table->tinyInteger('publipostage')->default('0');
            $table->tinyInteger('status')->default('0');
            $table->timestamps();
            $table->bigInteger('user_id');
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
