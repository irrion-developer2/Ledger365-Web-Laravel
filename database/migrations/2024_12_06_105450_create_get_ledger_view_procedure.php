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
        // Drop the procedure if it already exists to avoid conflicts
        DB::unprepared('DROP PROCEDURE IF EXISTS get_ledgerView_data;');

        // Create the stored procedure
        DB::unprepared("
            CREATE PROCEDURE get_ledgerView_data (
                IN p_ledgerId INT,
                IN p_company_ids VARCHAR(255),
                IN p_start_date DATE,
                IN p_end_date DATE
            )
            BEGIN
                DECLARE v_opening_balance DECIMAL(20,2);
                DECLARE v_pre_balance DECIMAL(20,2);

                -- Validate Input Parameters
                IF p_ledgerId IS NULL THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ledgerId cannot be NULL';
                END IF;

                IF p_company_ids IS NULL OR p_company_ids = '' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'company_ids cannot be NULL or empty';
                END IF;

                -- Fetch Opening Balance
                SELECT COALESCE(opening_balance, 0) INTO v_opening_balance
                FROM tally_ledgers
                WHERE ledger_id = p_ledgerId;

                -- Calculate Pre-Balance (Sum of transactions before p_start_date)
                IF p_start_date IS NOT NULL THEN
                    SELECT COALESCE(SUM(vh.amount), 0) INTO v_pre_balance
                    FROM tally_voucher_heads vh
                    JOIN tally_vouchers v ON vh.voucher_id = v.voucher_id
                    WHERE vh.ledger_id = p_ledgerId
                        AND FIND_IN_SET(v.company_id, p_company_ids) > 0
                        AND v.voucher_date < p_start_date
                        AND (v.is_optional IS NULL OR v.is_optional = FALSE);
                ELSE
                    SET v_pre_balance = 0;
                END IF;

                -- Common Table Expressions
                WITH RECURSIVE target_groups AS (
                    -- Get all ledger groups under 'Sales Account', 'Purchase Account', 'Bank Account'
                    SELECT
                        lg.ledger_group_id,
                        lg.ledger_group_name,
                        lg.parent
                    FROM tally_ledger_groups lg
                    WHERE lg.ledger_group_name IN ('Sales Account', 'Purchase Account', 'Bank Account')

                    UNION ALL

                    -- Recursively get child groups
                    SELECT
                        lgc.ledger_group_id,
                        lgc.ledger_group_name,
                        lgc.parent
                    FROM tally_ledger_groups lgc
                    INNER JOIN target_groups tg ON lgc.parent = tg.ledger_group_name
                ),
                counterpart_ledgers AS (
                    -- Get counterpart ledgers for each voucher without second recursion
                    SELECT
                        vh.voucher_id,
                        l.ledger_id,
                        l.ledger_name,
                        CASE
                            WHEN l.ledger_group_id IN (SELECT ledger_group_id FROM target_groups) THEN 1
                            ELSE 0
                        END AS is_target_group
                    FROM
                        tally_voucher_heads vh
                    INNER JOIN tally_ledgers l ON vh.ledger_id = l.ledger_id
                    WHERE
                        vh.voucher_id IN (
                            SELECT vh2.voucher_id
                            FROM tally_voucher_heads vh2
                            WHERE vh2.ledger_id = p_ledgerId
                        )
                        AND vh.ledger_id != p_ledgerId  -- Exclude the party ledger
                        AND (
                            (SELECT is_optional FROM tally_vouchers WHERE voucher_id = vh.voucher_id) IS NULL
                            OR (SELECT is_optional FROM tally_vouchers WHERE voucher_id = vh.voucher_id) = FALSE
                        )
                ),
                preferred_counterpart_ledgers AS (
                    SELECT
                        voucher_id,
                        GROUP_CONCAT(DISTINCT ledger_name SEPARATOR ', ') AS ledger_names
                    FROM
                        counterpart_ledgers
                    WHERE
                        is_target_group = 1
                    GROUP BY
                        voucher_id
                ),
                other_counterpart_ledgers AS (
                    SELECT
                        voucher_id,
                        GROUP_CONCAT(DISTINCT ledger_name SEPARATOR ', ') AS ledger_names
                    FROM
                        counterpart_ledgers
                    WHERE
                        is_target_group = 0
                    GROUP BY
                        voucher_id
                ),
                grouped_vouchers AS (
                    SELECT
                        v.voucher_date,
                        v.voucher_number,
                        v.voucher_id,
                        COALESCE(vt.voucher_type_name, 'Unknown') AS voucher_type_name,
                        MAX(vh.entry_type) AS entry_type,
                        SUM(vh.amount) AS total_amount,
                        COALESCE(pcl.ledger_names, ocl.ledger_names) AS counterpart_ledger_name
                    FROM
                        tally_voucher_heads vh
                    INNER JOIN tally_vouchers v ON vh.voucher_id = v.voucher_id
                    LEFT JOIN tally_voucher_types vt ON v.voucher_type_id = vt.voucher_type_id
                    LEFT JOIN preferred_counterpart_ledgers pcl ON vh.voucher_id = pcl.voucher_id
                    LEFT JOIN other_counterpart_ledgers ocl ON vh.voucher_id = ocl.voucher_id AND pcl.voucher_id IS NULL
                    WHERE
                        vh.ledger_id = p_ledgerId
                        AND FIND_IN_SET(v.company_id, p_company_ids) > 0
                        -- Optional Date Filtering
                        AND (
                            (p_start_date IS NULL AND p_end_date IS NULL)
                            OR (p_start_date IS NOT NULL AND p_end_date IS NOT NULL AND v.voucher_date BETWEEN p_start_date AND p_end_date)
                            OR (p_start_date IS NOT NULL AND p_end_date IS NULL AND v.voucher_date >= p_start_date)
                            OR (p_start_date IS NULL AND p_end_date IS NOT NULL AND v.voucher_date <= p_end_date)
                        )
                        AND (v.is_optional IS NULL OR v.is_optional = FALSE)
                    GROUP BY
                        v.voucher_id,
                        v.voucher_date,
                        v.voucher_number,
                        vt.voucher_type_name,
                        pcl.ledger_names,
                        ocl.ledger_names
                )

                -- Final Select with Running Balance Calculation
                SELECT
                    gv.voucher_date,
                    gv.voucher_number,
                    gv.voucher_type_name,
                    gv.entry_type,
                    ABS(gv.total_amount) AS net_amount,
                    gv.counterpart_ledger_name,
                    CASE 
                        WHEN gv.total_amount > 0 THEN gv.total_amount 
                        ELSE 0 
                    END AS credit_amount,
                    CASE 
                        WHEN gv.total_amount < 0 THEN ABS(gv.total_amount) 
                        ELSE 0 
                    END AS debit_amount,
                    (v_opening_balance + v_pre_balance + SUM(gv.total_amount) OVER (
                        ORDER BY gv.voucher_date, gv.voucher_number
                        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                    )) AS running_balance
                FROM
                    grouped_vouchers gv
                ORDER BY
                    gv.voucher_date, gv.voucher_number;
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
        // Drop the procedure when rolling back
        DB::unprepared('DROP PROCEDURE IF EXISTS get_ledgerView_data;');
    }
};
