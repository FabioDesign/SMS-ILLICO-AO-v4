<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->index('blacklist', 'idx_contacts_blacklist');
            $table->index('number', 'idx_contacts_number');
        });

        Schema::table('group_contact', function (Blueprint $table) {
            $table->index('contact_id', 'idx_gc_contact');
            $table->index('blacklist', 'idx_gc_blacklist');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->index('id', 'idx_groups_id');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('idx_contacts_blacklist');
            $table->dropIndex('idx_contacts_number');
        });

        Schema::table('group_contact', function (Blueprint $table) {
            $table->dropIndex('idx_gc_contact');
            $table->dropIndex('idx_gc_blacklist');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex('idx_groups_id');
        });
    }
};