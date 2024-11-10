<?php

namespace App\Services\Import;

use App\Models\TallyCompany;
use App\Repositories\Contracts\TallyCompanyRepositoryInterface;
use Illuminate\Support\Facades\Log;

class TallyCompanyService
{
    protected $tallyCompanyRepository;

    public function __construct(TallyCompanyRepositoryInterface $tallyCompanyRepository)
    {
        $this->tallyCompanyRepository = $tallyCompanyRepository;
    }

    public function importCompanyJson($request)
    {
        try {
            // $this->validateLicenseNumber($request);

            $jsonData = null;
            $fileName = 'tally_company_data_' . date('YmdHis') . '.json';

            if ($request->hasFile('uploadFile')) {
                $uploadedFile = $request->file('uploadFile');
                $jsonFilePath = storage_path('app/' . $fileName);
                $uploadedFile->move(storage_path('app'), $fileName);
                $jsonData = file_get_contents($jsonFilePath);
            } else {
                $jsonData = $request->getContent();
                $jsonFilePath = storage_path('app/' . $fileName);
                file_put_contents($jsonFilePath, $jsonData);
            }

            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
            }

            if (!isset($data['BODY']['DATA']['TALLYMESSAGE']['COMPANY'])) {
                throw new \Exception('COMPANY data not found in JSON structure.');
            }
            $companyData = $data['BODY']['DATA']['TALLYMESSAGE']['COMPANY'];
            // Log::info('Company data found in Tally Message', ['companyData' => $companyData]);

            $insertedRecordsCount = 0;

            $licenseNumber = $request->input('license_number');

            $tallyCompany = TallyCompany::updateOrCreate(
                [
                    'company_guid' => $companyData['GUID'][''] ?? null,
                ],
                [
                    'alter_id' => $companyData['ALTERID'][''] ?? null,
                    'company_name' => $companyData['NAME'][0] ?? null,
                    'state' => $companyData['STATENAME'][''] ?? null,
                    'license_number' => $licenseNumber,
                    'starting_from' => $companyData['STARTINGFROM'][''] ?? null,
                    'address' => isset($companyData['ADDRESS.LIST']['ADDRESS']) ? implode(", ", $companyData['ADDRESS.LIST']['ADDRESS']) : null,
                    'books_from' => $companyData['BOOKSFROM'][''] ?? null,
                    'audited_upto' => $companyData['AUDITEDUPTO'][''] ?? null,
                    'email' => $companyData['EMAIL'][''] ?? null,
                    'pincode' => $companyData['PINCODE'][''] ?? null,
                    'phone_number' => $companyData['PHONENUMBER'][''] ?? null,
                    'mobile_number' => $companyData['MOBILENUMBERS.LIST']['MOBILENUMBERS'] ?? null,
                    'income_tax_number' => $companyData['INCOMETAXNUMBER'][''] ?? null,
                    'company_number' => $companyData['COMPANYNUMBER'][''] ?? null,
                ]

            );

            $insertedRecordsCount++;

            return response()->json([
                'message' => 'Tally Company data processed successfully.',
                'records_inserted' => $insertedRecordsCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error in companyJsonImport function', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Private methods for data extraction and processing
}
