<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshStatisticUsers extends Command
{
    protected $signature = 'statistics:refresh-users {--debounce=3 : Secondes sans modification avant de recompter}';

    protected $description = 'Recompte les contacts et les groupes de chaque utilisateur dans statistic_users';

    public function handle(): int
    {
        $debounce = max(0, (int) $this->option('debounce'));

        // Heure limite calculée par MySQL (même horloge que les triggers NOW(3))
        $cut = DB::selectOne('SELECT NOW(3) - INTERVAL ? SECOND AS cut', [$debounce])->cut;

        // Crée la ligne statistic_users si elle n'existe pas encore
        DB::statement('
            INSERT INTO statistic_users (user_id, created_at, updated_at)
            SELECT DISTINCT p.user_id, NOW(), NOW()
              FROM statistic_users_pending p
             WHERE p.changed_at <= ?
               AND NOT EXISTS (SELECT 1 FROM statistic_users s WHERE s.user_id = p.user_id)
        ', [$cut]);

        // statistic_users.contacts = nombre de lignes de contacts
        DB::update("
            UPDATE statistic_users s
              JOIN statistic_users_pending p
                ON p.user_id = s.user_id AND p.type = 'contacts' AND p.changed_at <= ?
               SET s.contacts   = (SELECT COUNT(*) FROM contacts c WHERE c.user_id = s.user_id AND c.publipostage = 0),
                   s.updated_at = NOW()
        ", [$cut]);

        // statistic_users.groups = nombre de lignes de groups
        DB::update("
            UPDATE statistic_users s
              JOIN statistic_users_pending p
                ON p.user_id = s.user_id AND p.type = 'groups' AND p.changed_at <= ?
               SET s.`groups`   = (SELECT COUNT(*) FROM `groups` g WHERE g.user_id = s.user_id),
                   s.updated_at = NOW()
        ", [$cut]);

        // Retire les marqueurs traités (ceux modifiés après $cut restent pour le prochain passage)
        $deleted = DB::delete('DELETE FROM statistic_users_pending WHERE changed_at <= ?', [$cut]);

        $this->info("Utilisateurs recomptés : {$deleted}");

        return self::SUCCESS;
    }
}
