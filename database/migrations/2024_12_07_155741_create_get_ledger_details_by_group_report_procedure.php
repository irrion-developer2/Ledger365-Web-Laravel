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
        // Drop the procedure if it already exists
        DB::unprepared('DROP PROCEDURE IF EXISTS get_ledger_details_by_group;');

        // Create the stored procedure
        DB::unprepared("
            CREATE PROCEDURE get_ledger_details_by_group(
                IN p_company_ids VARCHAR(255),
                IN p_start_date DATE, 
                IN p_end_date DATE, 
                IN p_types VARCHAR(255),
                IN p_hierarchy_names VARCHAR(255)
            )
            BEGIN

            WITH RECURSIVE ledger_group_hierarchy AS (
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
                    AND FIND_IN_SET(CAST(lg.company_id AS CHAR), p_company_ids) > 0

                UNION ALL
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
                    FIND_IN_SET(CAST(lg_child.company_id AS CHAR), p_company_ids) > 0
            ),

            ledger_balances AS (
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
                            (v.voucher_date < p_start_date)
                            AND (v.is_optional = 0 OR v.is_optional IS NULL)
                            AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                            AND FIND_IN_SET(CAST(v.company_id AS CHAR), p_company_ids) > 0
                            AND p_start_date IS NOT NULL
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
                            (
                                (p_start_date IS NOT NULL AND p_end_date IS NOT NULL AND v.voucher_date BETWEEN p_start_date AND p_end_date)
                                OR (p_start_date IS NULL AND p_end_date IS NULL)
                            )
                            AND (v.is_optional = 0 OR v.is_optional IS NULL)
                            AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                            AND FIND_IN_SET(CAST(v.company_id AS CHAR), p_company_ids) > 0
                        GROUP BY
                            vh.ledger_id
                    ) vai ON vai.ledger_id = l.ledger_id
                WHERE
                    FIND_IN_SET(CAST(l.company_id AS CHAR), p_company_ids) > 0
            ),

            ledger_hierarchy AS (
                SELECT
                    lg_h.level + 1 AS level,
                    CONCAT(lg_h.full_group_path, ' > ', l.ledger_name) AS hierarchy,
                    'Ledger' AS type,
                    l.ledger_id AS id,
                    l.ledger_name AS name,
                    lb.adjusted_opening_balance AS opening_balance,
                    lb.total_debit,
                    lb.total_credit,
                    lb.closing_balance,
                    lg_h.ledger_group_name AS group_name
                FROM
                    ledger_balances lb
                INNER JOIN
                    tally_ledgers l ON lb.ledger_id = l.ledger_id
                INNER JOIN
                    ledger_group_hierarchy lg_h ON l.ledger_group_id = lg_h.ledger_group_id
            ),

            group_balances AS (
                SELECT
                    lg_h.level,
                    lg_h.full_group_path AS hierarchy,
                    'Group' AS type,
                    lg_h.ledger_group_id AS id,
                    lg_h.ledger_group_name AS name,
                    SUM(lh.opening_balance) AS opening_balance,
                    SUM(lh.total_debit) AS total_debit,
                    SUM(lh.total_credit) AS total_credit,
                    SUM(lh.closing_balance) AS closing_balance,
                    NULL AS group_name
                FROM
                    ledger_group_hierarchy lg_h
                LEFT JOIN
                    ledger_hierarchy lh ON lh.hierarchy LIKE CONCAT(lg_h.full_group_path, '%')
                GROUP BY
                    lg_h.level,
                    lg_h.full_group_path,
                    lg_h.ledger_group_id,
                    lg_h.ledger_group_name
            )
            SELECT
                fh.level,
                fh.hierarchy,
                fh.type,
                fh.id,
                fh.name,
                fh.opening_balance,
                fh.total_debit,
                fh.total_credit,
                fh.closing_balance
            FROM
                (
                    SELECT
                        level,
                        hierarchy,
                        type,
                        id,
                        name,
                        opening_balance,
                        total_debit,
                        total_credit,
                        closing_balance,
                        group_name
                    FROM
                        group_balances

                    UNION ALL

                    SELECT
                        level,
                        hierarchy,
                        type,
                        id,
                        name,
                        opening_balance,
                        total_debit,
                        total_credit,
                        closing_balance,
                        group_name
                    FROM
                        ledger_hierarchy
                ) fh
            WHERE 
                (p_types IS NULL OR p_types = '' OR FIND_IN_SET(fh.type, p_types) > 0)
                AND 
                (
                    p_hierarchy_names IS NULL OR p_hierarchy_names = ''
                    OR (
                        (fh.type = 'Group' AND FIND_IN_SET(fh.name, p_hierarchy_names) > 0)
                        OR (fh.type = 'Ledger' AND FIND_IN_SET(fh.group_name, p_hierarchy_names) > 0)
                    )
                )
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
        // Drop the stored procedure if it exists
        DB::unprepared('DROP PROCEDURE IF EXISTS get_ledger_details_by_group;');
    }
};
