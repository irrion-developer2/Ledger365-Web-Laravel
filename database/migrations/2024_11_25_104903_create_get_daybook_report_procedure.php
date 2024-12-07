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
                    v.voucher_id,
                    v.voucher_date,
                    COALESCE(pl.ledger_name, al.ledger_name) AS `ledger_name`,
                    COALESCE(pl.ledger_guid, al.ledger_guid) AS `ledger_guid`,
                    c.company_name,
                    vt.voucher_type_name,
                    v.voucher_number,
                    CASE
                        WHEN COALESCE(pl.entry_type, al.entry_type) = 'debit' THEN ABS(COALESCE(pl.amount, al.amount))
                        ELSE 0
                    END AS `total_debit`,
                    CASE
                        WHEN COALESCE(pl.entry_type, al.entry_type) = 'credit' THEN ABS(COALESCE(pl.amount, al.amount))
                        ELSE 0
                    END AS `total_credit`
                FROM
                    tally_vouchers v
                JOIN
                    tally_companies c ON v.company_id = c.company_id
                JOIN
                    tally_voucher_types vt ON v.voucher_type_id = vt.voucher_type_id
                LEFT JOIN (
                        SELECT
                            vh.voucher_id,
                            MIN(l.ledger_name) AS ledger_name,
                            MIN(vh.amount) AS amount,
                            MIN(vh.entry_type) AS entry_type,    
                            MIN(l.ledger_guid) AS ledger_guid
                        FROM
                            tally_voucher_heads vh
                        JOIN
                            tally_ledgers l ON vh.ledger_id = l.ledger_id
                        WHERE
                            vh.is_party_ledger = 1
                        GROUP BY
                            vh.voucher_id
                    ) pl ON v.voucher_id = pl.voucher_id

                LEFT JOIN (
                        SELECT
                            vh.voucher_id,
                            MIN(l.ledger_name) AS ledger_name,
                            MIN(vh.amount) AS amount,
                            MIN(vh.entry_type) AS entry_type,
                            MIN(l.ledger_guid) AS ledger_guid
                        FROM
                            tally_voucher_heads vh
                        JOIN
                            tally_ledgers l ON vh.ledger_id = l.ledger_id
                        GROUP BY
                            vh.voucher_id
                    ) al ON v.voucher_id = al.voucher_id AND pl.voucher_id IS NULL

                WHERE
                    v.is_optional = p_is_optional
                    AND v.is_cancelled = p_is_cancelled
                    AND FIND_IN_SET(v.company_id, p_company_ids)
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
