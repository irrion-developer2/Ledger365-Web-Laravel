APP_NAME=PreciseCA
APP_ENV=local
APP_KEY=base64:RYgsqb+1tDc1+hpC/onA7ZboEAsP/4a9zbLjqCMERiQ=
APP_DEBUG=true
APP_URL=http://127.0.0.1
APP_DOMAIN=localhost
DEBUGBAR_ENABLED=true


LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rocker-preciseca-sass
DB_USERNAME=root
DB_PASSWORD=


TENANT_DB_HOST=127.0.0.1
TENANT_DB_PORT=3306
TENANT_DB_USERNAME=root
TENANT_DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"





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




  To integrate the Bank Statement API with your Laravel application for processing PDF uploads, checking status, converting to JSON, and storing data, you'll need to follow these steps. Here's a detailed approach for handling the entire workflow:

Upload PDF
Check Upload Status
Convert PDF to JSON
Store Data in Database
Implementation
1. Upload PDF
Use the API to upload a PDF file. You'll need to handle the upload and extract the UUID for further processing.

php
Copy code
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

public function uploadPdf(Request $request)
{
    // Validate the uploaded PDF
    $request->validate([
        'pdf' => 'required|file|mimes:pdf',
    ]);

    // Upload the PDF
    $response = Http::withHeaders([
        'Authorization' => 'api-pdTP4fIva/IDMfMwCXTXLpZ76DNgkLX1zJPz9nojJb1SZOTcJ1asq1/ATg+qvvE3',
    ])->attach(
        'file', file_get_contents($request->file('pdf')), 'bankstatement.pdf'
    )->post('https://api2.bankstatementconverter.com/api/v1/BankStatement');

    $responseData = $response->json();

    if (isset($responseData[0]['uuid'])) {
        return response()->json([
            'uuid' => $responseData[0]['uuid'],
            'message' => 'PDF uploaded successfully',
        ]);
    }

    return response()->json(['error' => 'Failed to upload PDF'], 500);
}
2. Check Upload Status
Poll the status endpoint to check if the upload is processed. Implement polling with a delay if needed.

php
Copy code
public function checkStatus($uuid)
{
    $response = Http::withHeaders([
        'Authorization' => 'api-pdTP4fIva/IDMfMwCXTXLpZ76DNgkLX1zJPz9nojJb1SZOTcJ1asq1/ATg+qvvE3',
    ])->post('https://api2.bankstatementconverter.com/api/v1/BankStatement/status', [$uuid]);

    $responseData = $response->json();

    if ($responseData[0]['state'] === 'READY') {
        return response()->json([
            'status' => 'READY',
            'message' => 'PDF processing is complete',
        ]);
    }

    return response()->json([
        'status' => 'PROCESSING',
        'message' => 'PDF is still processing',
    ]);
}
3. Convert PDF to JSON
Once the PDF is processed, use the conversion endpoint to get the JSON data.

php
Copy code
public function convertPdfToJson($uuid)
{
    $response = Http::withHeaders([
        'Authorization' => 'api-pdTP4fIva/IDMfMwCXTXLpZ76DNgkLX1zJPz9nojJb1SZOTcJ1asq1/ATg+qvvE3',
    ])->post('https://api2.bankstatementconverter.com/api/v1/BankStatement/convert?format=JSON', [$uuid]);

    $jsonData = $response->json();

    return response()->json($jsonData);
}
4. Store Data in Database
Finally, process the JSON data and store it in your database.

php
Copy code
public function storeData(Request $request)
{
    $jsonData = $request->input('data'); // Assume 'data' contains the JSON response

    foreach ($jsonData['normalised'] as $transaction) {
        BankReconciliation::create([
            'transaction_date' => $transaction['date'],
            'narration' => $transaction['description'],
            'withdrawl' => $transaction['amount'] < 0 ? abs($transaction['amount']) : null,
            'deposit' => $transaction['amount'] > 0 ? $transaction['amount'] : null,
            'balance' => $transaction['balance'] ?? null,
        ]);
    }

    return response()->json(['message' => 'Data stored successfully']);
}
