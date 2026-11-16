<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nettoyage des anciens triggers éventuels
        DB::unprepared('DROP TRIGGER IF EXISTS statistic_users');

        // Quand users.volume diminue (soustraction), la quantité retirée
        // est ajoutée à :
        //  - statistic_users.sendsms (clé : user_id)
        //  - statistic_sms.volume    (clés : user_id, year, month)
        DB::unprepared("
            CREATE TRIGGER statistic_users
            AFTER UPDATE ON users
            FOR EACH ROW
            BEGIN
                DECLARE v_sub DECIMAL(10,0);
                SET v_sub = IFNULL(OLD.volume, 0) - IFNULL(NEW.volume, 0);

                IF v_sub > 0 THEN

                    -- statistic_users.sendsms
                    IF EXISTS (SELECT 1 FROM statistic_users WHERE user_id = NEW.id) THEN
                        UPDATE statistic_users
                           SET sendsms    = sendsms + v_sub,
                               updated_at = NOW()
                         WHERE user_id = NEW.id;
                    ELSE
                        INSERT INTO statistic_users (sendsms, user_id, created_at, updated_at)
                        VALUES (v_sub, NEW.id, NOW(), NOW());
                    END IF;

                    -- statistic_sms.volume
                    IF EXISTS (
                        SELECT 1 FROM statistic_sms
                         WHERE user_id = NEW.id AND year = YEAR(NOW()) AND month = MONTH(NOW())
                    ) THEN
                        UPDATE statistic_sms
                           SET volume     = volume + v_sub,
                               updated_at = NOW()
                         WHERE user_id = NEW.id AND year = YEAR(NOW()) AND month = MONTH(NOW());
                    ELSE
                        INSERT INTO statistic_sms (year, month, volume, user_id, created_at, updated_at)
                        VALUES (YEAR(NOW()), MONTH(NOW()), v_sub, NEW.id, NOW(), NOW());
                    END IF;

                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS statistic_users');
    }
};
