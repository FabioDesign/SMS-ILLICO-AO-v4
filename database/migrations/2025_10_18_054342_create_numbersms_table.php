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
        Schema::create('numbersms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid');
            $table->string('number', 20);
            $table->decimal('volume_sms', 2, 0);
            $table->timestamp('sending_at')->nullable();
            $table->tinyInteger('status')->default('0');
            $table->bigInteger('bodysms_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numbersms');
    }
};
