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
        $procedure = "
                CREATE PROCEDURE DeleteMultipleCompaniesData(IN p_company_ids VARCHAR(255))
            BEGIN
                START TRANSACTION;

                    -- Delete from tally_vouchers (Cascades to tally_voucher_heads, tally_voucher_items, etc.)
                    DELETE FROM tally_vouchers
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Delete from user_company_mappings
                    DELETE FROM user_company_mappings
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Delete from tally_voucher_types
                    DELETE FROM tally_voucher_types
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Delete from tally_currencies
                    DELETE FROM tally_currencies
                    WHERE FIND_IN_SET(company_id, p_company_ids);
                    
                    -- Delete from tally_godowns
                    DELETE FROM tally_godowns
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Delete from tally_items
                    DELETE FROM tally_items
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Delete from tally_units
                    DELETE FROM tally_units
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Delete from tally_item_groups
                    DELETE FROM tally_item_groups
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Delete from tally_ledgers
                    DELETE FROM tally_ledgers
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Delete from tally_ledger_groups
                    DELETE FROM tally_ledger_groups
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Finally, delete from tally_companies
                    DELETE FROM tally_companies
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    COMMIT;
                END
            ";


            DB::unprepared('DROP PROCEDURE IF EXISTS DeleteMultipleCompaniesData');

            DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS DeleteMultipleCompaniesData');
    }
};
