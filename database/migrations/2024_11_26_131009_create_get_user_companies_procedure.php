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
            CREATE PROCEDURE GetUserCompaniesData(IN p_user_id INT)
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    -- Rollback the transaction if any error occurs
                    ROLLBACK;
                    -- Signal a custom error message
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An error occurred while retrieving company data.';
                END;

                -- Start the transaction
                START TRANSACTION;

                -- Retrieve and return the company data
                SELECT c.*
                FROM tally_companies c
                JOIN user_company_mappings ucm ON c.company_id = ucm.company_id
                WHERE ucm.user_id = p_user_id;

                -- Commit the transaction
                COMMIT;
            END
        ";

        // Drop the procedure if it already exists to avoid duplication errors
        DB::unprepared('DROP PROCEDURE IF EXISTS GetUserCompaniesData');

        // Create the stored procedure
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS GetUserCompaniesData');
    }
};
