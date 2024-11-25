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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_balance_sheet_data;');

        DB::unprepared("
                CREATE PROCEDURE get_balance_sheet_data(
                    IN p_company_ids VARCHAR(255),
                    IN p_start_date DATE,
                    IN p_end_date DATE,
                    IN p_ledger_group_name VARCHAR(1000)
                )
                BEGIN
                    -- Handle empty string for p_ledger_group_name
                    SET p_ledger_group_name = IF(CHAR_LENGTH(TRIM(p_ledger_group_name)) = 0, NULL, p_ledger_group_name);

                    WITH RECURSIVE ledger_group_hierarchy AS (
                        SELECT
                            tlg.ledger_group_id,
                            tlg.ledger_group_name,
                            tlg.parent,
                            CAST(tlg.ledger_group_name AS CHAR(1000)) AS full_group_path,
                            0 AS level
                        FROM
                            tally_ledger_groups tlg
                        WHERE
                            FIND_IN_SET(tlg.company_id, p_company_ids)
                            AND (
                                p_ledger_group_name IS NULL
                                OR FIND_IN_SET(tlg.ledger_group_name COLLATE utf8mb4_unicode_ci, p_ledger_group_name COLLATE utf8mb4_unicode_ci)
                            )
                        UNION ALL
                        SELECT
                            tlg_child.ledger_group_id,
                            tlg_child.ledger_group_name,
                            tlg_child.parent,
                            CAST(CONCAT(tlg_h.full_group_path, ' > ', tlg_child.ledger_group_name) AS CHAR(1000)) AS full_group_path,
                            tlg_h.level + 1 AS level
                        FROM
                            tally_ledger_groups tlg_child
                        INNER JOIN
                            ledger_group_hierarchy tlg_h ON tlg_child.parent = tlg_h.ledger_group_name
                        WHERE
                            FIND_IN_SET(tlg_child.company_id, p_company_ids)
                            AND tlg_h.level < 10
                    ),
                    voucher_amounts_before AS (
                        SELECT
                            tvh.ledger_id,
                            SUM(tvh.amount) AS total_amount
                        FROM
                            tally_voucher_heads tvh
                        INNER JOIN
                            tally_vouchers tv ON tvh.voucher_id = tv.voucher_id
                        WHERE
                            (tv.is_optional = 0 OR tv.is_optional IS NULL)
                            AND (tv.is_cancelled = 0 OR tv.is_cancelled IS NULL)
                            AND FIND_IN_SET(tv.company_id, p_company_ids)
                            AND (p_start_date IS NULL OR tv.voucher_date < p_start_date) 
                        GROUP BY
                            tvh.ledger_id
                    ),
                    voucher_amounts_in_range AS (
                        SELECT
                            tvh.ledger_id,
                            SUM(tvh.amount) AS total_amount,
                            SUM(CASE WHEN tvh.amount < 0 THEN ABS(tvh.amount) ELSE 0 END) AS debit_amount,
                            SUM(CASE WHEN tvh.amount > 0 THEN tvh.amount ELSE 0 END) AS credit_amount
                        FROM
                            tally_voucher_heads tvh
                        INNER JOIN
                            tally_vouchers tv ON tvh.voucher_id = tv.voucher_id
                        WHERE
                            (tv.is_optional = 0 OR tv.is_optional IS NULL)
                            AND (tv.is_cancelled = 0 OR tv.is_cancelled IS NULL)
                            AND FIND_IN_SET(tv.company_id, p_company_ids)
                            AND (p_start_date IS NULL OR tv.voucher_date >= p_start_date)
                            AND (p_end_date IS NULL OR tv.voucher_date <= p_end_date)
                        GROUP BY
                            tvh.ledger_id
                    ),
                    ledger_balances AS (
                        SELECT
                            tl.ledger_id,
                            tl.ledger_name,
                            tlg_h.full_group_path AS ledger_group_hierarchy,
                            (IFNULL(tl.opening_balance, 0) + IFNULL(vab.total_amount, 0)) AS closing_balance,
                            ABS(IFNULL(vai.debit_amount, 0)) AS total_debit,
                            IFNULL(vai.credit_amount, 0) AS total_credit,
                            (IFNULL(tl.opening_balance, 0) + IFNULL(vab.total_amount, 0) + ABS(IFNULL(vai.debit_amount, 0)) - IFNULL(vai.credit_amount, 0)) AS opening_balance
                        FROM
                            ledger_group_hierarchy tlg_h
                        INNER JOIN
                            tally_ledgers tl ON tl.ledger_group_id = tlg_h.ledger_group_id
                        LEFT JOIN
                            voucher_amounts_before vab ON vab.ledger_id = tl.ledger_id
                        LEFT JOIN
                            voucher_amounts_in_range vai ON vai.ledger_id = tl.ledger_id
                        WHERE
                            FIND_IN_SET(tl.company_id, p_company_ids)
                    )
                    SELECT
                        lb.ledger_group_hierarchy,
                        SUM(lb.opening_balance) AS opening_balance,
                        SUM(lb.total_debit) AS total_debit,
                        SUM(lb.total_credit) AS total_credit,
                        SUM(lb.closing_balance) AS closing_balance
                    FROM
                        ledger_balances lb
                    GROUP BY
                        lb.ledger_group_hierarchy
                    ORDER BY
                        lb.ledger_group_hierarchy;
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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_balance_sheet_data;');
    }
};
