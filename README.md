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

git token : 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


in cpanal tenant workable steps 
config->app.php
config->tenancy.php

2}=DPm=P23t}

User: irriion
Database: irriion

User: irriion
Database: tenant_b07b0971-f830-4ee1-91d8-570ff0760278

on those two datatable has same user and password

and changes on .env(sample-.env.serverexample) files and tenancy or other domain changes in code

in local Imporsonate server error
http://pristm.preciseca.com:8000/

changes on _impersonate.blade.php
and changes on main db domain table subdomain name and in tenant db users record fill

This command will forcefully remove the .trash directory and all its contents, including any files or subdirectories it contains. Be cautious when using the -rf flags, as they can cause irreversible data loss if used incorrectly.

After removing the .trash directory, it will be permanently deleted from your server. Make sure that you don't need any files or data stored within this directory before proceeding with the deletion.

rm -rf /home/preciseca/.trash
rm -rf /home/preciseca/public_html/laraveltenanttallyconnector/.git

newly steup for existing project
Start Date : 13-07-2024
* Template refer - https://codervent.com/rocker/demo/horizontal/index.html
* Create a MySQL database
* Download composer
* Copy `.env.example` file to `.env` .
* Open the console and cd your project root directory
* Run `composer install or php composer.phar install`
* Run `php artisan key:generate`
* composer require laravel/breeze --dev
* php artisan breeze:install
* Run `php artisan migrate`

* protected $connection = 'mysql'; add these line to model if you want to display a that 
  table to subdomain tenant
