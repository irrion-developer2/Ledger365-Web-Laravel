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

                -- Common Table Expression for Target Ledger Groups
                WITH RECURSIVE target_groups AS (
                    SELECT
                        lg.ledger_group_id,
                        lg.ledger_group_name,
                        lg.parent
                    FROM
                        tally_ledger_groups lg
                    WHERE
                        lg.ledger_group_name NOT IN ('Sundry Debtors', 'Sundry Creditors', 'Duties & Taxes')

                    UNION ALL

                    SELECT
                        lgc.ledger_group_id,
                        lgc.ledger_group_name,
                        lgc.parent
                    FROM
                        tally_ledger_groups lgc
                            INNER JOIN target_groups tg ON lgc.parent = tg.ledger_group_name
                ),

                -- Subquery to Group Vouchers by voucher_id
                grouped_vouchers AS (
                    SELECT
                        v.voucher_date,
                        v.voucher_number,
                        v.voucher_id,
                        COALESCE(vt.voucher_type_name, 'Unknown') AS voucher_type_name,
                        MAX(vh.entry_type) AS entry_type,
                        SUM(vh.amount) AS total_amount,
                        (
                            SELECT l2.ledger_name
                            FROM tally_voucher_heads vh2
                                JOIN tally_ledgers l2 ON vh2.ledger_id = l2.ledger_id
                                JOIN tally_ledger_groups lg2 ON l2.ledger_group_id = lg2.ledger_group_id
                            WHERE vh2.voucher_id = v.voucher_id
                                AND vh2.ledger_id != p_ledgerId
                                AND lg2.ledger_group_name NOT IN ('Sundry Debtors', 'Sundry Creditors', 'Duties & Taxes')      
                            ORDER BY l2.ledger_name DESC
                            LIMIT 1
                        ) AS counterpart_ledger_name
                    FROM
                        tally_voucher_heads vh
                            INNER JOIN tally_vouchers v ON vh.voucher_id = v.voucher_id
                            LEFT JOIN tally_voucher_types vt ON v.voucher_type_id = vt.voucher_type_id
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
                        vt.voucher_type_name
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
