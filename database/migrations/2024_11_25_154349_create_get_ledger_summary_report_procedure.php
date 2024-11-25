<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS get_ledger_summary_data;');

        DB::unprepared("
            CREATE PROCEDURE get_ledger_summary_data (
                IN p_company_ids VARCHAR(255),
                IN p_start_date DATE,
                IN p_end_date DATE
            )
            BEGIN
                WITH RECURSIVE ledger_group_hierarchy AS (
                    -- Base case: select top-level ledger groups (parent IS NULL)
                    SELECT
                        lg.ledger_group_id,
                        lg.ledger_group_name,
                        lg.parent AS parent_group_name,
                        CAST(NULL AS SIGNED) AS parent_ledger_group_id,
                        CAST(lg.ledger_group_name AS CHAR(1000)) AS full_group_path,
                        0 AS level
                    FROM
                        tally_ledger_groups lg
                    WHERE
                        (lg.parent IS NULL OR COALESCE(lg.parent, '') = '')
                        AND FIND_IN_SET(lg.company_id, p_company_ids)
                
                    UNION ALL
                
                    -- Recursive case: select child ledger groups
                    SELECT
                        lg_child.ledger_group_id,
                        lg_child.ledger_group_name,
                        lg_child.parent AS parent_group_name,
                        lg_h.ledger_group_id AS parent_ledger_group_id,
                        CAST(CONCAT(lg_h.full_group_path, ' > ', lg_child.ledger_group_name) AS CHAR(1000)) AS full_group_path,
                        lg_h.level + 1 AS level
                    FROM
                        tally_ledger_groups lg_child
                    INNER JOIN
                        ledger_group_hierarchy lg_h ON lg_child.parent = lg_h.ledger_group_name
                    WHERE
                        FIND_IN_SET(lg_child.company_id, p_company_ids)
                ),
                ledger_balances AS (
                    -- Compute balances for each ledger
                    SELECT
                        l.ledger_id,
                        l.ledger_name,
                        l.ledger_group_id,
                        (IFNULL(l.opening_balance, 0) + IFNULL(vab.total_amount, 0)) AS adjusted_opening_balance,
                        ABS(IFNULL(vai.debit_amount, 0)) AS total_debit,
                        IFNULL(vai.credit_amount, 0) AS total_credit,
                        (IFNULL(l.opening_balance, 0) + IFNULL(vab.total_amount, 0) + IFNULL(vai.total_amount, 0)) AS closing_balance
                    FROM
                        tally_ledgers l
                    LEFT JOIN
                        (
                            SELECT
                                vh.ledger_id,
                                SUM(vh.amount) AS total_amount
                            FROM
                                tally_voucher_heads vh
                            INNER JOIN
                                tally_vouchers v ON vh.voucher_id = v.voucher_id
                            WHERE
                                v.voucher_date < IFNULL(p_start_date, '1000-01-01')
                                AND (v.is_optional = 0 OR v.is_optional IS NULL)
                                AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                                AND FIND_IN_SET(v.company_id, p_company_ids)
                            GROUP BY
                                vh.ledger_id
                        ) vab ON vab.ledger_id = l.ledger_id
                    LEFT JOIN
                        (
                            SELECT
                                vh.ledger_id,
                                SUM(vh.amount) AS total_amount,
                                SUM(CASE WHEN vh.amount < 0 THEN vh.amount ELSE 0 END) AS debit_amount,
                                SUM(CASE WHEN vh.amount > 0 THEN vh.amount ELSE 0 END) AS credit_amount
                            FROM
                                tally_voucher_heads vh
                            INNER JOIN
                                tally_vouchers v ON vh.voucher_id = v.voucher_id
                            WHERE
                                (v.is_optional = 0 OR v.is_optional IS NULL)
                                AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                                AND (
                                    (v.voucher_date BETWEEN p_start_date AND p_end_date)
                                    OR (p_start_date IS NULL AND p_end_date IS NULL)
                                )
                                AND FIND_IN_SET(v.company_id, p_company_ids)
                            GROUP BY
                                vh.ledger_id
                        ) vai ON vai.ledger_id = l.ledger_id
                    WHERE
                        FIND_IN_SET(l.company_id, p_company_ids)
                ),
                ledger_hierarchy AS (
                    -- Combine ledgers with their full hierarchical paths and balances
                    SELECT
                        lg_h.level + 1 AS level,
                        CONCAT(lg_h.full_group_path, ' > ', l.ledger_name) AS hierarchy,
                        'Ledger' AS type,
                        l.ledger_id AS id,
                        l.ledger_name AS name,
                        l.ledger_guid,
                        lb.adjusted_opening_balance AS opening_balance,
                        lb.total_debit,
                        lb.total_credit,
                        lb.closing_balance,
                        l.ledger_name AS final_ledger_name
                    FROM
                        ledger_group_hierarchy lg_h
                    INNER JOIN
                        tally_ledgers l ON l.ledger_group_id = lg_h.ledger_group_id
                    INNER JOIN
                        ledger_balances lb ON lb.ledger_id = l.ledger_id
                )
                -- Final selection
                SELECT
                    level,
                    hierarchy,
                    type,
                    id,
                    name,
                    ledger_guid,
                    opening_balance,
                    total_debit,
                    total_credit,
                    closing_balance,
                    final_ledger_name
                FROM
                    ledger_hierarchy
                ORDER BY
                    hierarchy;
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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_ledger_summary_data;');
    }
};
