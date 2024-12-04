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
        $procedure = "
            CREATE PROCEDURE get_stock_item_procedure(IN company_ids INT)
            BEGIN
                SELECT
                    i.item_name,
                    i.hsn_code,
                    i.opening_rate,
                    (IFNULL(i.opening_balance, 0) 
                     + IFNULL(s.total_purchases_qty, 0) 
                     + IFNULL(s.total_sales_qty, 0)) AS closing_stock,
                    IFNULL(s.total_purchases_amount, 0) AS total_purchases_amount,
                    IFNULL(s.total_sales_amount, 0) AS total_sales_amount,
                    (IFNULL(s.total_purchases_amount, 0) + IFNULL(s.total_sales_amount, 0)) AS overall_amount
                FROM tally_items i
                LEFT JOIN (
                    SELECT 
                        vi.item_id, 
                        SUM(
                            CASE 
                                WHEN vt.voucher_type_name = 'Purchase' THEN vi.actual_qty 
                                WHEN vt.voucher_type_name = 'Credit Note' THEN -vi.actual_qty 
                                ELSE 0 
                            END
                        ) AS total_purchases_qty,
                        SUM(
                            CASE 
                                WHEN vt.voucher_type_name = 'Sales' THEN -vi.actual_qty 
                                WHEN vt.voucher_type_name = 'Debit Note' THEN vi.actual_qty 
                                ELSE 0 
                            END
                        ) AS total_sales_qty,
                        SUM(
                            CASE 
                                WHEN vt.voucher_type_name = 'Purchase' THEN vi.amount 
                                WHEN vt.voucher_type_name = 'Credit Note' THEN -vi.amount 
                                ELSE 0 
                            END
                        ) AS total_purchases_amount,
                        SUM(
                            CASE 
                                WHEN vt.voucher_type_name = 'Sales' THEN vi.amount 
                                WHEN vt.voucher_type_name = 'Debit Note' THEN -vi.amount 
                                ELSE 0 
                            END
                        ) AS total_sales_amount
                    FROM tally_voucher_items vi
                    JOIN tally_voucher_heads vh ON vi.voucher_head_id = vh.voucher_head_id
                    JOIN tally_vouchers v ON vh.voucher_id = v.voucher_id
                    JOIN tally_voucher_types vt ON v.voucher_type_id = vt.voucher_type_id
                    WHERE vt.voucher_type_name IN ('Sales', 'Purchase', 'Debit Note', 'Credit Note')  
                      AND v.is_cancelled = 0 
                      AND v.is_optional = 0
                      AND FIND_IN_SET(v.company_id, company_ids)
                    GROUP BY vi.item_id
                ) s ON i.item_id = s.item_id
                WHERE 
                FIND_IN_SET(i.company_id, company_ids)
                ORDER BY i.item_name ASC;
            END
        ";

        DB::unprepared('DROP PROCEDURE IF EXISTS get_stock_item_procedure');

        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS get_stock_item_procedure');
    }
};
