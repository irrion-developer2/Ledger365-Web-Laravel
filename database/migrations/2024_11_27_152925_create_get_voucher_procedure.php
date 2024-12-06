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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_voucher_data;');

        DB::unprepared("
            CREATE PROCEDURE get_voucher_data (
                IN p_company_ids VARCHAR(255),
                IN p_voucher_type_name VARCHAR(100),
                IN p_start_date DATE,
                IN p_end_date DATE
            )
            BEGIN
            
                SET p_voucher_type_name = IF(CHAR_LENGTH(TRIM(p_voucher_type_name)) = 0, NULL, p_voucher_type_name); 
                SET p_start_date = IFNULL(p_start_date, NULL);
                SET p_end_date = IFNULL(p_end_date, NULL);
                
                SELECT 
                        tl.ledger_name,
                        tl.ledger_guid,
                        c.company_name,
                        tv.voucher_id,
                        tv.voucher_date,
                        tv.voucher_type_id,
                        tv.voucher_number,
                        ABS(tvh.amount) AS invoice_amount,
                        tv.place_of_supply
                    FROM 
                        tally_vouchers tv
                    LEFT JOIN 
                        tally_voucher_heads tvh ON tv.voucher_id = tvh.voucher_id
                    LEFT JOIN 
                        tally_ledgers tl ON tvh.ledger_id = tl.ledger_id
                    LEFT JOIN 
                        tally_voucher_types tvt ON tv.voucher_type_id = tvt.voucher_type_id
                    LEFT JOIN
                        tally_companies c ON tv.company_id = c.company_id 
                    WHERE 
                        voucher_type_name IS NULL
                        OR FIND_IN_SET(tvt.voucher_type_name , p_voucher_type_name)
                        AND FIND_IN_SET(tv.company_id, p_company_ids)
                        AND tvh.is_party_ledger = 1
                        AND (p_start_date IS NULL OR tv.voucher_date >= p_start_date)
                        AND (p_end_date IS NULL OR tv.voucher_date <= p_end_date)
                    ORDER BY 
                        tv.voucher_date;
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
        DB::unprepared('DROP PROCEDURE IF EXISTS get_voucher_data;');
    }
};
