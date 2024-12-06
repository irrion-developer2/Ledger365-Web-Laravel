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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_MonthlyReport_data;');

        // Create the stored procedure with multiple IDs handling
        DB::unprepared("
            CREATE PROCEDURE get_MonthlyReport_data(
                IN p_voucher_type_names VARCHAR(255),
                IN company_ids VARCHAR(255),
                IN p_start_date DATE,
                IN p_end_date DATE
            )
            BEGIN

                -- Handle NULL dates if necessary
                SET p_start_date = IFNULL(p_start_date, NULL);
                SET p_end_date = IFNULL(p_end_date, NULL);

                WITH VoucherAmounts AS (
                    SELECT 
                        tv.voucher_id,
                        tv.company_id,
                        tv.voucher_date,
                        tvt.voucher_type_name,
                        FIRST_VALUE(ABS(tvh.amount)) OVER (
                            PARTITION BY tv.voucher_id 
                            ORDER BY CASE 
                                WHEN tvh.is_party_ledger = 1 
                                AND (
                                    (tvt.voucher_type_name = 'Sales' AND tvh.entry_type = 'debit') OR 
                                    (tvt.voucher_type_name = 'Purchase' AND tvh.entry_type = 'credit')
                                )
                                THEN 1 
                                ELSE 2 
                            END
                            ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
                        ) AS voucher_amount
                    FROM 
                        tally_vouchers tv
                        INNER JOIN tally_voucher_types tvt ON tv.voucher_type_id = tvt.voucher_type_id
                        INNER JOIN tally_voucher_heads tvh ON tv.voucher_id = tvh.voucher_id
                        INNER JOIN tally_ledgers l ON tvh.ledger_id = l.ledger_id
                        INNER JOIN tally_ledger_groups lg ON l.ledger_group_id = lg.ledger_group_id
                    WHERE 
                        FIND_IN_SET(tvt.voucher_type_name, p_voucher_type_names) > 0
                        AND FIND_IN_SET(tv.company_id, company_ids) > 0
                        AND (tv.is_optional IS NULL OR tv.is_optional = FALSE)
                        AND (tv.is_cancelled IS NULL OR tv.is_cancelled = FALSE)
                        AND (
                            p_start_date IS NULL
                            OR p_end_date IS NULL
                            OR tv.voucher_date BETWEEN p_start_date AND p_end_date
                        )
                ),
                MonthlyTotals AS (
                    SELECT 
                        company_id,
                        voucher_date,
                        voucher_id,
                        voucher_type_name,
                        MAX(voucher_amount) AS total_amount
                    FROM 
                        VoucherAmounts
                    GROUP BY 
                        company_id,
                        voucher_date,
                        voucher_id,
                        voucher_type_name
                )

                SELECT
                    c.company_name,
                    c.company_id,
                    tvt.voucher_type_name,
                    MAX(MONTHNAME(tv.voucher_date)) AS month_name,
                    YEAR(tv.voucher_date) AS year,
                    MONTH(tv.voucher_date) AS month,
                    COUNT(DISTINCT tv.voucher_id) AS total_vouchers,
                    COALESCE(SUM(mt.total_amount), 0) AS total_amount
                FROM
                    tally_vouchers tv
                    INNER JOIN tally_voucher_types tvt ON tv.voucher_type_id = tvt.voucher_type_id
                    INNER JOIN tally_companies c ON tv.company_id = c.company_id
                    LEFT JOIN MonthlyTotals mt ON tv.voucher_id = mt.voucher_id
                WHERE
                    FIND_IN_SET(tvt.voucher_type_name, p_voucher_type_names) > 0
                    AND FIND_IN_SET(c.company_id, company_ids) > 0
                    AND (tv.is_optional IS NULL OR tv.is_optional = FALSE)
                    AND (tv.is_cancelled IS NULL OR tv.is_cancelled = FALSE)
                    AND (
                        p_start_date IS NULL
                        OR p_end_date IS NULL
                        OR tv.voucher_date BETWEEN p_start_date AND p_end_date
                    )
                GROUP BY
                    c.company_name,
                    c.company_id,
                    tvt.voucher_type_name,
                    YEAR(tv.voucher_date),
                    MONTH(tv.voucher_date)
                ORDER BY
                    tvt.voucher_type_name,
                    YEAR(tv.voucher_date),
                    MONTH(tv.voucher_date);
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
        // Drop the procedure if it exists
        DB::unprepared('DROP PROCEDURE IF EXISTS get_MonthlyReport_data;');
    }
};
