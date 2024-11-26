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
                    DECLARE EXIT HANDLER FOR SQLEXCEPTION
                    BEGIN
                        -- Rollback the transaction if any error occurs
                        ROLLBACK;
                        -- Signal an error message
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An error occurred while deleting company data.';
                    END;

                    START TRANSACTION;

                    DELETE FROM tally_companies
                    WHERE FIND_IN_SET(company_id, p_company_ids);

                    -- Commit the transaction
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
