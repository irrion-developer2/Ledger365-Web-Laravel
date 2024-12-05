<?php

namespace App\Services\Import;

use Carbon\Carbon;
use App\Models\TallyCompany;
use App\Models\TallyVoucherType;
use App\Services\TallyLicenseCheck;
use Illuminate\Support\Facades\Log;

class TallyVoucherTypeService
{
    protected $tallyLicenseCheck;

    public function __construct(TallyLicenseCheck $tallyLicenseCheck)
    {
        $this->tallyLicenseCheck = $tallyLicenseCheck;
    }

    public function importVoucherTypeJson($request)
    {
        try {
            $this->tallyLicenseCheck->validateLicenseNumber($request);

            $jsonData = null;
            $fileName = 'tally_voucher_type_data_' . date('YmdHis') . '.json';

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

            if (!isset($data['BODY']['DATA']['COLLECTION']['VOUCHERTYPE'])) {
                throw new \Exception('VOUCHERTYPE data not found in JSON structure.');
            }

            $insertedRecordsCount = 0;
            foreach ($data['BODY']['DATA']['COLLECTION']['VOUCHERTYPE'] as $voucherTypeData) {

                // Access GUID as a string
                $guid = $voucherTypeData['GUID'][''] ?? null;
                if ($guid) {
                    $companyGuid = substr($guid, 0, 36);
                    $company = TallyCompany::where('company_guid', $companyGuid)->first();
                    if (!$company) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue;
                    }
                    $companyId = $company->company_id;

                        try {
                            $tallyVoucherType = TallyVoucherType::updateOrCreate(
                                [
                                    'voucher_type_guid' => $guid,
                                ],
                                [
                                    'company_id' => $companyId,
                                    'alter_id' => $voucherTypeData['ALTERID'][''] ?? null,
                                    'voucher_type_name' => $voucherTypeData['NAME'] ?? null,
                                    'parent' => $voucherTypeData['PARENT'][''] ?? null,
                                    'numbering_method' => $voucherTypeData['NUMBERINGMETHOD'][''] ?? null,
                                    'prevent_duplicate' => isset($voucherTypeData['PREVENTDUPLICATES']) && $voucherTypeData['PREVENTDUPLICATES'][''] === 'Yes',
                                    'use_zero_entries' => isset($voucherTypeData['USEZEROENTRIES']) && $voucherTypeData['USEZEROENTRIES'][''] === 'Yes',
                                    'is_deemed_positive' => isset($voucherTypeData['ISDEEMEDPOSITIVE']) && $voucherTypeData['ISDEEMEDPOSITIVE'][''] === 'Yes',
                                    'affects_stock' => isset($voucherTypeData['AFFECTSSTOCK']) && $voucherTypeData['AFFECTSSTOCK'][''] === 'Yes',
                                    'is_active' => isset($voucherTypeData['ISACTIVE']) && $voucherTypeData['ISACTIVE'][''] === 'Yes',
                                ]
                            );
                            if ($tallyVoucherType) {
                                $insertedRecordsCount++;
                            }
                        } catch (\Exception $e) {
                        // log in detail
                        Log::error('Error creating voucher type record: ' . $e->getMessage(), [
                            'voucherTypeData' => $voucherTypeData,
                            'companyId' => $companyId,
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Tally voucher type data processed successfully.',
                'records_inserted' => $insertedRecordsCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error in voucherTypeJsonImport function', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
