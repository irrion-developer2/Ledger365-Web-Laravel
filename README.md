* Create a MySQL database
* Download composer
* Copy `.env.example` file to `.env` .
* Open the console and cd your project root directory
* Run `composer install or php composer.phar install`
* Run `php artisan key:generate`
* Run `php artisan migrate`
* php artisan migrate:tenants
* php artisan migrate:rollback --batch 4
* Make Test Domain Like `central.test` 
* Change `APP_URL` to `http://central.test`in `.env`
* Run `php artisan db:Seed --class=TenantSeeder`
* composer require stancl/tenancy:^3.6
* php artisan tenancy:install
* also setup route

Command to check collation of database:
* SHOW VARIABLES LIKE 'collation%'
* SET collation_server = 'utf8mb4_unicode_ci';
* SET collation_database = 'utf8mb4_unicode_ci';
* SET collation_server = 'utf8mb4_unicode_ci';

**[mysqld]**
* collation_server = utf8mb4_unicode_ci
* character_set_server = utf8mb4

**Log commands to check collation**
* SHOW VARIABLES LIKE 'collation%';
* SET collation_server = 'utf8mb4_unicode_ci';
* SELECT VERSION();

**Commands to pull and merge changes from main branch**
* git checkout ledger-365
* git fetch origin
* git merge origin/main
* git add .
* git commit - m "Commit message"
