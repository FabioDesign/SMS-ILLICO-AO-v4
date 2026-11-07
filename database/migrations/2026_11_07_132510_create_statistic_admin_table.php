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
        Schema::create('statistic_admin', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->decimal('sms_balance', 10, 0)->default(0);
            $table->decimal('sms_sent', 10, 0)->default(0);
            $table->decimal('sms_unitel', 10, 0)->default(0);
            $table->decimal('sms_movicel', 10, 0)->default(0);
            $table->decimal('sms_africel', 10, 0)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistic_admin');
    }
};
