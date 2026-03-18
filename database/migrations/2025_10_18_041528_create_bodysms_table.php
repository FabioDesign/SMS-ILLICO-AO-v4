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
        Schema::create('bodysms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid');
            $table->string('sender', 15);
            $table->text('message');
            $table->decimal('volume_sms', 10, 0);
            $table->decimal('chars', 10, 0);
            $table->decimal('pages', 10, 0);
            $table->timestamp('sending_at');
            $table->tinyInteger('status')->default('0');
            $table->timestamps();
            $table->integer('smstype_id');
            $table->bigInteger('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bodysms');
    }
};
