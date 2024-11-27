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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_MonthlyReport_data;');

        DB::unprepared("
            CREATE PROCEDURE get_MonthlyReport_data(
                IN p_voucher_type_name VARCHAR(100),
                IN p_company_ids VARCHAR(255),
                IN p_start_date DATE,
                IN p_end_date DATE
            )
            BEGIN
            WITH monthly_data AS (
                SELECT
                YEAR(v.voucher_date) AS year,
                MONTH(v.voucher_date) AS month,
                SUM(CASE WHEN vh.entry_type = 'debit' THEN vh.amount ELSE 0 END) AS total_debit,
                SUM(CASE WHEN vh.entry_type = 'credit' THEN vh.amount ELSE 0 END) AS total_credit
                FROM
                tally_vouchers v
                JOIN tally_voucher_types vt ON v.voucher_type_id = vt.voucher_type_id COLLATE utf8mb4_0900_ai_ci
                JOIN tally_voucher_heads vh ON v.voucher_id = vh.voucher_id
                WHERE
                vt.voucher_type_name = p_voucher_type_name COLLATE utf8mb4_0900_ai_ci
                AND v.is_optional = 0
                AND v.is_cancelled = 0
                GROUP BY
                YEAR(v.voucher_date),
                MONTH(v.voucher_date)
            ),
            cumulative_data AS (
                SELECT
                md.year,
                md.month,
                md.total_debit,
                md.total_credit,
                SUM(md.total_credit - md.total_debit) OVER (ORDER BY md.year, md.month ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS closing_balance
                FROM
                monthly_data md
            )
            SELECT
                CONCAT(md.year, '-', LPAD(md.month, 2, '0')) AS month_year,
                md.total_debit,
                md.total_credit,
                md.closing_balance
            FROM
                cumulative_data md
            ORDER BY
                md.year,
                md.month;
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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_MonthlyReport_data;');
    }
};
