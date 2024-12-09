<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations to create the stored procedure.
     *
     * @return void
     */
    public function up()
    {
        // Drop the procedure if it already exists to avoid conflicts
        DB::unprepared('DROP PROCEDURE IF EXISTS get_stock_item_procedure');

        // Define the stored procedure with nullable date parameters
        $procedure = "
            CREATE PROCEDURE get_stock_item_procedure (
                IN p_company_ids VARCHAR(255),
                IN p_start_date DATE,
                IN p_end_date DATE
            )
            BEGIN
                WITH StockMovements AS (
                -- Get initial stock from items table
                SELECT
                    i.item_id,
                    i.hsn_code,
                    i.item_name,
                    COALESCE(i.opening_balance, 0) as movement_qty,
                    COALESCE(i.opening_value, 0) as movement_value,
                    COALESCE(i.opening_value / NULLIF(i.opening_balance, 0), 0) as movement_rate
                FROM tally_items i
                WHERE i.company_id = 1  -- Replace with your company_id

                UNION ALL

                -- Get stock movements from voucher items
                SELECT
                    vi.item_id,
                    NULL AS hsn_code,
                    i.item_name,
                    CASE
                        WHEN vt.affects_stock = 1 AND v.is_cancelled = 0 AND
                            (vt.is_deemed_positive = 1 OR vh.is_deemed_positive = 1)
                            THEN COALESCE(vi.actual_qty, 0)
                        WHEN vt.affects_stock = 1 AND v.is_cancelled = 0
                            THEN -COALESCE(vi.actual_qty, 0)
                        ELSE 0
                        END as movement_qty,
                    CASE
                        WHEN vt.affects_stock = 1 AND v.is_cancelled = 0 AND
                            (vt.is_deemed_positive = 1 OR vh.is_deemed_positive = 1)
                            THEN COALESCE(vi.amount, 0)
                        WHEN vt.affects_stock = 1 AND v.is_cancelled = 0
                            THEN -COALESCE(vi.amount, 0)
                        ELSE 0
                        END as movement_value,
                    COALESCE(vi.rate, 0) as movement_rate
                FROM tally_voucher_items vi
                        INNER JOIN tally_items i ON vi.item_id = i.item_id
                        INNER JOIN tally_voucher_heads vh ON vi.voucher_head_id = vh.voucher_head_id
                        INNER JOIN tally_vouchers v ON vh.voucher_id = v.voucher_id
                        INNER JOIN tally_voucher_types vt ON v.voucher_type_id = vt.voucher_type_id
                WHERE  FIND_IN_SET(v.company_id, p_company_ids) > 0
                AND (
                    p_start_date IS NULL
                    OR p_end_date IS NULL
                    OR v.voucher_date BETWEEN p_start_date AND p_end_date
                )
            )

            SELECT
                item_id,
                item_name,
                hsn_code,
                SUM(movement_qty) as opening_qty,
                SUM(movement_value) as closing_value,
                CASE
                    WHEN SUM(movement_qty) = 0 THEN 0
                    ELSE SUM(movement_value) / SUM(movement_qty)
                    END as average_rate
            FROM StockMovements
            GROUP BY item_id, item_name, hsn_code
            HAVING SUM(movement_qty) != 0
            ORDER BY item_name;
            END
        ";

        // Create the stored procedure
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations by dropping the stored procedure.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS get_stock_item_procedure');
    }
};
