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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_daybook_data;');

        DB::unprepared("
            CREATE PROCEDURE get_daybook_data(
                IN p_company_ids VARCHAR(255),
                IN p_start_date DATE,
                IN p_end_date DATE,
                IN p_is_cancelled TINYINT(1),
                IN p_is_optional TINYINT(1),
                IN p_voucher_type_name VARCHAR(255)
            )
            BEGIN
            
                SET p_start_date = IFNULL(p_start_date, NULL);
                SET p_end_date = IFNULL(p_end_date, NULL);
                SET p_voucher_type_name = NULLIF(TRIM(p_voucher_type_name), '');
                   

                SELECT
                    v.voucher_date,
                    GROUP_CONCAT(l.ledger_name SEPARATOR ', ') AS `ledger_name`,
                    c.company_name,
                    vt.voucher_type_name,
                    v.voucher_number,
                    SUM(
                        CASE 
                            WHEN vh.entry_type = 'debit' THEN ABS(vh.amount) 
                            ELSE 0 
                        END
                    ) AS `total_debit`,
                    SUM(
                        CASE 
                            WHEN vh.entry_type = 'credit' THEN vh.amount 
                            ELSE 0 
                        END
                    ) AS `total_credit`
                FROM
                    tally_vouchers v
                JOIN
                    tally_companies c ON v.company_id = c.company_id
                JOIN
                    tally_voucher_types vt ON v.voucher_type_id = vt.voucher_type_id
                JOIN
                    tally_voucher_heads vh ON v.voucher_id = vh.voucher_id
                JOIN
                    tally_ledgers l ON vh.ledger_id = l.ledger_id
                WHERE
                    v.is_optional = p_is_optional
                    AND v.is_cancelled = p_is_cancelled
                    AND FIND_IN_SET(l.company_id, p_company_ids)
                    AND (p_start_date IS NULL OR v.voucher_date >= p_start_date)
                    AND (p_end_date IS NULL OR v.voucher_date <= p_end_date)
                    AND (
                        p_voucher_type_name IS NULL
                        OR FIND_IN_SET(vt.voucher_type_name, p_voucher_type_name)
                    ) 
                GROUP BY
                    v.voucher_id,
                    v.voucher_date,
                    c.company_name,
                    vt.voucher_type_name,
                    v.voucher_number
                ORDER BY
                    v.voucher_date ASC;
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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_daybook_data;');
    }
};
