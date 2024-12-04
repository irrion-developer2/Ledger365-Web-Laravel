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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_MonthlyDetailReport_data;');

        DB::unprepared("
            CREATE PROCEDURE get_MonthlyDetailReport_data(
                IN p_voucher_type_name VARCHAR(100),
                IN p_company_id INT,
                IN p_start_date DATE,
                IN p_end_date DATE,
                IN p_entry_types VARCHAR(255)
            )
            BEGIN
                SELECT
                    tv.voucher_id,
                    tv.voucher_date,
                    tvh.entry_type,
                    tvh.amount,
                    c.company_name
                FROM
                    tally_vouchers tv
                INNER JOIN
                    tally_voucher_types tvt
                    ON tv.voucher_type_id = tvt.voucher_type_id
                INNER JOIN
                    tally_voucher_heads tvh
                    ON tv.voucher_id = tvh.voucher_id
                INNER JOIN
                    tally_companies c ON tv.company_id = c.company_id
                WHERE
                    tvt.voucher_type_name = p_voucher_type_name
                    AND tv.company_id = p_company_id
                    AND tv.voucher_date BETWEEN p_start_date AND p_end_date
                    AND (
                        p_entry_types IS NULL
                        OR p_entry_types = ''
                        OR FIND_IN_SET(
                            LOWER(tvh.entry_type),
                            LOWER(p_entry_types)
                        ) > 0
                    )
                    AND (tv.is_optional IS NULL OR tv.is_optional = FALSE)
                    AND (tv.is_cancelled IS NULL OR tv.is_cancelled = FALSE)
                ORDER BY
                    tv.voucher_date, tv.voucher_id;
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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_MonthlyDetailReport_data;');
    }
};
