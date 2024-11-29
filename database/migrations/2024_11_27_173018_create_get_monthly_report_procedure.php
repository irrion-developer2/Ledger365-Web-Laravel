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
                    IN company_ids TEXT,
                    IN p_start_date DATE,
                    IN p_end_date DATE,
                    IN p_entry_types VARCHAR(255)
            )
            BEGIN
            
                SET p_start_date = IFNULL(p_start_date, NULL);
                SET p_end_date = IFNULL(p_end_date, NULL);

                SELECT
                    YEAR(tv.voucher_date) AS `Year`,
                    MONTH(tv.voucher_date) AS `Month`,
                    SUM(
                        CASE 
                            WHEN FIND_IN_SET(tvh.entry_type, p_entry_types) > 0 
                            THEN tvh.amount 
                            ELSE 0 
                        END
                    ) AS `Total_Amount`
                FROM
                    tally_vouchers tv
                INNER JOIN
                    tally_voucher_types tvt 
                    ON tv.voucher_type_id = tvt.voucher_type_id
                INNER JOIN
                    tally_voucher_heads tvh 
                    ON tv.voucher_id = tvh.voucher_id
                WHERE
                    tvt.voucher_type_name = p_voucher_type_name
                    AND FIND_IN_SET(tv.company_id, company_ids) > 0
                    AND tv.voucher_date BETWEEN p_start_date AND p_end_date
                    AND (
                        p_entry_types IS NULL 
                        OR p_entry_types = ''
                        OR FIND_IN_SET(tvh.entry_type, p_entry_types) > 0
                    )
                    AND (tv.is_optional IS NULL OR tv.is_optional = FALSE)
                    AND (tv.is_cancelled IS NULL OR tv.is_cancelled = FALSE)
                GROUP BY
                    YEAR(tv.voucher_date),
                    MONTH(tv.voucher_date)
                ORDER BY
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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_MonthlyReport_data;');
    }
};
