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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid');
            $table->string('reference', 15);
            $table->decimal('volume1_sms', 10, 0);
            $table->decimal('volume2_sms', 10, 0);
            $table->decimal('price', 5, 0)->default('0');
            $table->decimal('fees', 5, 0)->default('0');
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
        Schema::dropIfExists('bills');
    }
};
