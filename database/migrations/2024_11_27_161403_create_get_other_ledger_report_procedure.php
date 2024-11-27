<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS get_other_ledger_data;');

        DB::unprepared("
            CREATE PROCEDURE get_other_ledger_data (
                IN company_ids TEXT,
                IN start_date DATE,
                IN end_date DATE,
                IN p_excluded_group_names VARCHAR(255) 
            )
            BEGIN
                DECLARE companyIdsList TEXT;
                SET companyIdsList = company_ids;
                SET start_date = IFNULL(start_date, NULL);
                SET end_date = IFNULL(end_date, NULL);

                 WITH RECURSIVE ledger_group_hierarchy AS (
                    SELECT
                        lg.ledger_group_id,
                        lg.ledger_group_name,
                        lg.parent
                    FROM
                        tally_ledger_groups lg
                    WHERE
                        (p_excluded_group_names IS NULL 
                         OR lg.ledger_group_name COLLATE utf8mb4_unicode_ci NOT IN (
                             SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p_excluded_group_names, ',', numbers.n), ',', -1))
                             FROM (
                                 SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                                 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
                                 UNION ALL SELECT 9 UNION ALL SELECT 10
                             ) numbers
                             WHERE numbers.n <= 1 + LENGTH(p_excluded_group_names) - LENGTH(REPLACE(p_excluded_group_names, ',', ''))
                         )
                        )
                        AND FIND_IN_SET(lg.company_id, company_ids) > 0

                    UNION ALL

                    SELECT
                        lg_child.ledger_group_id,
                        lg_child.ledger_group_name,
                        lg_child.parent
                    FROM
                        tally_ledger_groups lg_child
                    INNER JOIN
                        ledger_group_hierarchy lg_parent
                        ON lg_child.parent = lg_parent.ledger_group_name
                        AND FIND_IN_SET(lg_child.company_id, company_ids) > 0
                    WHERE
                        (p_excluded_group_names IS NULL 
                         OR lg_child.ledger_group_name COLLATE utf8mb4_unicode_ci NOT IN (
                             SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p_excluded_group_names, ',', numbers.n), ',', -1))
                             FROM (
                                 SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                                 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
                                 UNION ALL SELECT 9 UNION ALL SELECT 10
                             ) numbers
                             WHERE numbers.n <= 1 + LENGTH(p_excluded_group_names) - LENGTH(REPLACE(p_excluded_group_names, ',', ''))
                         )
                        )
                )
                SELECT
                    l.ledger_name,
                    l.ledger_guid,
                    c.company_name,
                    lg_h.ledger_group_name, -- Included ledger_group_name
                    l.party_gst_in AS gstin,
                    (
                        IFNULL(l.opening_balance, 0) 
                        + IFNULL(ob.total_transactions_before_start_date, 0)
                    ) AS opening_balance_as_of_start_date,
                    IFNULL(tp.total_transactions_in_period, 0) AS transactions_in_period,
                    (
                        IFNULL(l.opening_balance, 0)
                        + IFNULL(ob.total_transactions_before_start_date, 0)
                        + IFNULL(tp.total_transactions_in_period, 0)
                    ) AS outstanding
                FROM
                    tally_ledgers l
                INNER JOIN
                    ledger_group_hierarchy lg_h ON l.ledger_group_id = lg_h.ledger_group_id
                INNER JOIN
                    tally_companies c ON l.company_id = c.company_id
                LEFT JOIN (
                    SELECT
                        vh.ledger_id,
                        SUM(vh.amount) AS total_transactions_before_start_date
                    FROM
                        tally_voucher_heads vh
                    INNER JOIN
                        tally_vouchers v ON vh.voucher_id = v.voucher_id
                    WHERE
                        FIND_IN_SET(v.company_id, company_ids) > 0
                        AND (v.voucher_date < start_date)
                        AND (v.is_optional = 0 OR v.is_optional IS NULL)
                        AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                    GROUP BY
                        vh.ledger_id
                ) ob ON l.ledger_id = ob.ledger_id
                LEFT JOIN (
                    SELECT
                        vh.ledger_id,
                        SUM(vh.amount) AS total_transactions_in_period
                    FROM
                        tally_voucher_heads vh
                    INNER JOIN
                        tally_vouchers v ON vh.voucher_id = v.voucher_id
                    WHERE
                        FIND_IN_SET(v.company_id, company_ids) > 0
                        AND (
                            (v.voucher_date BETWEEN start_date AND end_date)
                            OR (start_date IS NULL AND end_date IS NULL)
                        )
                        AND (v.is_optional = 0 OR v.is_optional IS NULL)
                        AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                    GROUP BY
                        vh.ledger_id
                ) tp ON l.ledger_id = tp.ledger_id
                WHERE
                    FIND_IN_SET(l.company_id, company_ids) > 0
                ORDER BY
                    l.ledger_name;
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS get_other_ledger_data;');
    }
};
