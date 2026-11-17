<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Le recomptage n'est plus fait par un EVENT MySQL mais par la
        // commande Artisan "statistics:refresh-users" (voir RefreshStatisticUsers).
        DB::unprepared('DROP EVENT IF EXISTS ev_refresh_statistic_users');

        // ---------------------------------------------------------------
        // Table tampon : "quel utilisateur doit être recompté, et quand
        // a eu lieu la dernière modification ?"
        // ---------------------------------------------------------------
        Schema::create('statistic_users_pending', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigInteger('user_id');
            $table->string('type', 10);            // 'contacts' ou 'groups'
            $table->timestamp('changed_at', 3);    // précision milliseconde
            $table->primary(['user_id', 'type']);
        });

        // ---------------------------------------------------------------
        // Triggers légers : ils ne font que "marquer" l'utilisateur
        // (pas de COUNT pendant les imports massifs)
        // ---------------------------------------------------------------
        foreach (['contacts', 'groups'] as $type) {
            foreach (['insert' => 'NEW', 'delete' => 'OLD'] as $action => $row) {
                $name = "{$type}_pending_after_{$action}";
                DB::unprepared("DROP TRIGGER IF EXISTS {$name}");
                DB::unprepared("
                    CREATE TRIGGER {$name}
                    AFTER " . strtoupper($action) . " ON `{$type}`
                    FOR EACH ROW
                    BEGIN
                        INSERT INTO statistic_users_pending (user_id, type, changed_at)
                        VALUES ({$row}.user_id, '{$type}', NOW(3))
                        ON DUPLICATE KEY UPDATE changed_at = NOW(3);
                    END
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['contacts', 'groups'] as $type) {
            foreach (['insert', 'delete'] as $action) {
                DB::unprepared("DROP TRIGGER IF EXISTS {$type}_pending_after_{$action}");
            }
        }

        Schema::dropIfExists('statistic_users_pending');
    }
};
