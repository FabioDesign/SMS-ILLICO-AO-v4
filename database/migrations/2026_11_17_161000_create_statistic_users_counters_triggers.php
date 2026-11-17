<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Délai (en secondes) sans nouvelle modification avant de recompter.
     * Doit rester cohérent avec la valeur utilisée dans l'EVENT ci-dessous.
     */
    private const DEBOUNCE = 3;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---------------------------------------------------------------
        // 1. Table tampon : "quel utilisateur doit être recompté, et quand
        //    a eu lieu la dernière modification ?"
        // ---------------------------------------------------------------
        Schema::create('statistic_users_pending', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigInteger('user_id');
            $table->string('type', 10);            // 'contacts' ou 'groups'
            $table->timestamp('changed_at', 3);    // précision milliseconde
            $table->primary(['user_id', 'type']);
        });

        // ---------------------------------------------------------------
        // 2. Triggers légers : ils ne font que "marquer" l'utilisateur
        //    (pas de COUNT pendant les imports massifs)
        // ---------------------------------------------------------------
        foreach (['contacts', 'groups'] as $type) {
            foreach (['insert' => 'NEW', 'delete' => 'OLD'] as $action => $row) {
                $name = "{$type}_pending_after_{$action}";
                DB::unprepared("DROP TRIGGER IF EXISTS {$name}");
                DB::unprepared("
                    CREATE TRIGGER {$name}
                    AFTER " . strtoupper($action) . " ON {$type}
                    FOR EACH ROW
                    BEGIN
                        INSERT INTO statistic_users_pending (user_id, type, changed_at)
                        VALUES ({$row}.user_id, '{$type}', NOW(3))
                        ON DUPLICATE KEY UPDATE changed_at = NOW(3);
                    END
                ");
            }
        }

        // ---------------------------------------------------------------
        // 3. EVENT : toutes les 2 secondes, recompte les utilisateurs dont
        //    la dernière modification date de plus de 3 secondes.
        //    Un EVENT ne s'exécute jamais en parallèle de lui-même, donc
        //    aucun chevauchement n'est possible.
        // ---------------------------------------------------------------
        $d = self::DEBOUNCE;

        DB::unprepared('DROP EVENT IF EXISTS ev_refresh_statistic_users');
        DB::unprepared("
            CREATE EVENT ev_refresh_statistic_users
            ON SCHEDULE EVERY 2 SECOND
            DO
            BEGIN
                DECLARE v_cut DATETIME(3);
                SET v_cut = NOW(3) - INTERVAL {$d} SECOND;

                -- Crée la ligne statistic_users si elle n'existe pas encore
                INSERT INTO statistic_users (user_id, created_at, updated_at)
                SELECT DISTINCT p.user_id, NOW(), NOW()
                  FROM statistic_users_pending p
                 WHERE p.changed_at <= v_cut
                   AND NOT EXISTS (SELECT 1 FROM statistic_users s WHERE s.user_id = p.user_id);

                -- statistic_users.contacts = nombre de lignes de contacts
                UPDATE statistic_users s
                  JOIN statistic_users_pending p
                    ON p.user_id = s.user_id AND p.type = 'contacts' AND p.changed_at <= v_cut
                   SET s.contacts   = (SELECT COUNT(*) FROM contacts c WHERE c.user_id = s.user_id),
                       s.updated_at = NOW();

                -- statistic_users.groups = nombre de lignes de groups
                UPDATE statistic_users s
                  JOIN statistic_users_pending p
                    ON p.user_id = s.user_id AND p.type = 'groups' AND p.changed_at <= v_cut
                   SET s.`groups`   = (SELECT COUNT(*) FROM `groups` g WHERE g.user_id = s.user_id),
                       s.updated_at = NOW();

                -- Retire les marqueurs traités (ceux modifiés après v_cut restent)
                DELETE FROM statistic_users_pending WHERE changed_at <= v_cut;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP EVENT IF EXISTS ev_refresh_statistic_users');

        foreach (['contacts', 'groups'] as $type) {
            foreach (['insert', 'delete'] as $action) {
                DB::unprepared("DROP TRIGGER IF EXISTS {$type}_pending_after_{$action}");
            }
        }

        Schema::dropIfExists('statistic_users_pending');
    }
};
