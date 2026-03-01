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
        Schema::create('group_contact', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('blacklist')->default('0');
            $table->bigInteger('contact_id');
            $table->bigInteger('group_id');
            $table->unique(['group_id', 'contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_group');
    }
};
