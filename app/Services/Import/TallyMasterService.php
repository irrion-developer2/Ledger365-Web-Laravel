<?php

namespace App\Services\Import;

use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyCompany;
use App\Models\TallyCurrency;
use App\Models\TallyLedgerGroup;
use App\Services\TallyLicenseCheck;
use Illuminate\Support\Facades\Log;

class TallyMasterService
{
    protected $tallyLicenseCheck;

    public function __construct(TallyLicenseCheck $tallyLicenseCheck)
    {
        $this->tallyLicenseCheck = $tallyLicenseCheck;
    }

    public function importMasterJson($request)
    {
        try {

            $this->tallyLicenseCheck->validateLicenseNumber($request);

            $jsonData = null;
            $fileName = 'tally_master_data_' . date('YmdHis') . '.json';


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
            $result = $this->tallyLicenseCheck->findTallyMessage($data);

            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }

            $messagesPath = $result['path'];
            $messages = $result['value'];

            $currencyCount = 0;
            $groupCount = 0;
            $ledgerCount = 0;
            $companyIds = [];


            foreach ($messages as $message) {
                if (isset($message['CURRENCY'])) {
                    $currencyData = $message['CURRENCY'];
                    // Log::info('Currency Data:', ['currencyData' => $currencyData]);

                    $guid = $currencyData['GUID'] ?? null;
                    $companyGuid = substr($guid, 0, 36);

                    $company = TallyCompany::where('company_guid', $companyGuid)->first();

                    if (!$company) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue;
                    }

                    $companyId = $company->company_id;

                    try {
                        $tallyCurrency = TallyCurrency::updateOrCreate(
                            ['currency_guid' => $currencyData['GUID'] ?? null,],
                            [
                                'alter_id' => $currencyData['ALTERID'] ?? null,
                                'company_id' => $companyId,
                                'currency_name' => $currencyData['MAILINGNAME'] ?? null,
                                'symbol' => $currencyData['EXPANDEDSYMBOL'] ?? null,
                                'decimal_symbol' => $currencyData['DECIMALSYMBOL'] ?? null,
                                'decimal_places' => $currencyData['DECIMALPLACES'] ?? null,
                            ]
                        );
                        if ($tallyCurrency) {
                            $currencyCount++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error creating currency record: ' . $e->getMessage(), [
                            'currencyData' => $currencyData,
                            'companyId' => $companyId,
                        ]);
                    }

                    if (!$tallyCurrency) {
                        throw new \Exception('Failed to create or update tally ledger Currency record.');
                    }
                }
            }

            foreach ($messages as $message) {
                if (isset($message['GROUP'])) {
                    $groupData = $message['GROUP'];
                    // Log::info('Group Data:', ['groupData' => $groupData]);

                    $nameField = $groupData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? null;
                    if (is_array($nameField)) {
                        $nameField = implode(', ', $nameField);
                    }

                    $guid = $groupData['GUID'] ?? null;
                    $companyGuid = substr($guid, 0, 36);

                    $company = TallyCompany::where('company_guid', $companyGuid)->first();

                    if (!$company) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue;
                    }

                    $companyId = $company->company_id;
                    $companyIds[$companyId] = true;

                    $parent = $groupData['PARENT'] ?? null;
                    $isPrimary = empty($parent);


                    try {
                        $tallyLedgerGroup = TallyLedgerGroup::updateOrCreate(
                            ['ledger_group_guid' => $guid],
                            [
                                'company_id' => $companyId,
                                'parent' => $groupData['PARENT'] ?? null,
                                'affects_stock' => isset($groupData['AFFECTSSTOCK']) && $groupData['AFFECTSSTOCK'] === 'Yes',
                                'alter_id' => $groupData['ALTERID'] ?? null,
                                'ledger_group_name' => $groupData['NAME'] ?? null,
                                'is_primary' => $isPrimary,
                            ]
                        );
                        if ($tallyLedgerGroup) {
                            $groupCount++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error creating ledger group record: ' . $e->getMessage(), [
                            'groupData' => $groupData,
                            'companyId' => $companyId,
                        ]);
                    }

                    if (!$tallyLedgerGroup) {
                        throw new \Exception('Failed to create or update tally ledger group record.');
                    }

                }
            }

            foreach (array_keys($companyIds) as $companyId) {
                $groups = TallyLedgerGroup::where('company_id', $companyId)->get();
                $groupsByName = [];
                foreach ($groups as $group) {
                    $groupsByName[$group->ledger_group_name] = $group;
                }

                foreach ($groups as $group) {
                    $primaryGroupName = $this->tallyLicenseCheck->getPrimaryGroup($group, $groupsByName);
                    $group->primary_group = $primaryGroupName;
                    $group->save();
                    // Log::info('Updated primary_group for group', ['group_id' => $group->id, 'primary_group' => $primaryGroupName]);
                }
            }

            foreach ($messages as $message) {
                if (isset($message['LEDGER'])) {
                    $ledgerData = $message['LEDGER'];
                    // Log::info('Ledger Data:', ['ledgerData' => $ledgerData]);

                    $applicableFrom = null;
                    if (isset($ledgerData['LEDGSTREGDETAILS.LIST']['APPLICABLEFROM'])) {
                        $applicableFrom = Carbon::createFromFormat('Ymd', $ledgerData['LEDGSTREGDETAILS.LIST']['APPLICABLEFROM'])->format('Y-m-d');
                    }

                    $addressList = $ledgerData['LEDMAILINGDETAILS.LIST']['ADDRESS.LIST']['ADDRESS'] ?? null;
                    if (is_array($addressList)) {
                        $addressList = implode(', ', $addressList);
                    }

                    $mailingApplicableFrom = null;
                    if (isset($ledgerData['LEDMAILINGDETAILS.LIST']['APPLICABLEFROM'])) {
                        $mailingApplicableFrom = Carbon::createFromFormat('Ymd', $ledgerData['LEDMAILINGDETAILS.LIST']['APPLICABLEFROM'])->format('Y-m-d');
                    }

                    $guid = $ledgerData['GUID'] ?? null;
                    $companyGuid = substr($guid, 0, 36);

                    $company = TallyCompany::where('company_guid', $companyGuid)->first();

                    if (!$company) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue;
                    }

                    $companyId = $company->company_id;

                    $nameField = $ledgerData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? [];
                    $aliases = [];
                    if (is_array($nameField)) {
                        $languageName = $nameField[0] ?? null;
                        for ($i = 1; $i < count($nameField); $i++) {
                            $aliases[] = $nameField[$i] ?? null;
                        }
                    } else {
                        $languageName = $nameField;
                    }

                    $alias1 = $aliases[0] ?? null;
                    $alias2 = $aliases[1] ?? null;
                    $alias3 = $aliases[2] ?? null;

                    $parent = $ledgerData['PARENT'] ?? null;
                    $ledgerGroup = TallyLedgerGroup::where('ledger_group_name', $parent)
                        ->where('company_id', $companyId)
                        ->first();
                    $ledgerGroupId = $ledgerGroup ? $ledgerGroup->ledger_group_id : null;

                    $pincode = null;
                    if (isset($ledgerData['LEDMAILINGDETAILS.LIST']['PINCODE'])) {
                        $pincodeValue = $ledgerData['LEDMAILINGDETAILS.LIST']['PINCODE'];
                        if (ctype_digit($pincodeValue)) {
                            $pincode = $pincodeValue;
                        }
                    }

                    $party_gst_in = null;
                    if (isset($ledgerData['PARTYGSTIN'])) {
                        $partyGstIn = $ledgerData['PARTYGSTIN'];
                        if (strlen($partyGstIn) == 15) {
                            $party_gst_in = $partyGstIn;
                        }
                    }

                    $gst_in = null;
                    if (isset($ledgerData['LEDGSTREGDETAILS.LIST']['GSTIN'])) {
                        $gstIn = $ledgerData['LEDGSTREGDETAILS.LIST']['GSTIN'];
                        if (strlen($gstIn) == 15) {
                            $gst_in = $gstIn;
                        }
                    }

                    $email = $ledgerData['EMAIL'] ?? null;

                    if (is_array($email)) {
                        Log::info('Email is an array', ['email' => $email]);
                        // Extract the value with the empty string key
                        $email = $email[""] ?? null;
                        Log::info('Extracted email value', ['email' => $email]);
                    }

                    if (is_string($email) && strlen($email) > 255) {
                        $email = substr($email, 0, 255);
                    } else {
                        $email = "";
                    }

                    try {
                        $tallyLedger = TallyLedger::updateOrCreate(
                            ['ledger_guid' => $guid],
                            [
                                'company_id' => $companyId,
                                'ledger_group_id' => $ledgerGroupId,
                                'alter_id' => $ledgerData['ALTERID'] ?? null,
                                'ledger_name' => $languageName,
                                'alias1' => $alias1,
                                'alias2' => $alias2,
                                'alias3' => $alias3,
                                'parent' => $ledgerData['PARENT'] ?? null,
                                'tax_classification_name' => html_entity_decode($ledgerData['TAXCLASSIFICATIONNAME'] ?? null),
                                'tax_type' => $ledgerData['TAXTYPE'] ?? null,
                                'bill_credit_period' => $ledgerData['BILLCREDITPERIOD'] ?? null,
                                'credit_limit' => !empty($ledgerData['CREDITLIMIT']) ? $ledgerData['CREDITLIMIT'] : null,
                                'gst_type' => html_entity_decode($ledgerData['GSTTYPE'] ?? null),
                                'party_gst_in' => $party_gst_in,
                                'gst_duty_head' => $ledgerData['GSTDUTYHEAD'] ?? null,
                                'service_category' => html_entity_decode($ledgerData['SERVICECATEGORY'] ?? null),
                                'gst_registration_type' => $ledgerData['GSTREGISTRATIONTYPE'] ?? null,
                                'excise_ledger_classification' => html_entity_decode($ledgerData['EXCISELEDGERCLASSIFICATION'] ?? null),
                                'excise_duty_type' => html_entity_decode($ledgerData['EXCISEDUTYTYPE'] ?? null),
                                'excise_nature_of_purchase' => html_entity_decode($ledgerData['EXCISENATUREOFPURCHASE'] ?? null),
                                'is_bill_wise_on' => isset($ledgerData['ISBILLWISEON']) && $ledgerData['ISBILLWISEON'] === 'Yes',
                                'is_cost_centres_on' => isset($ledgerData['ISCOSTCENTRESON']) && $ledgerData['ISCOSTCENTRESON'] === 'Yes',
                                'opening_balance' => !empty($ledgerData['OPENINGBALANCE']) ? $ledgerData['OPENINGBALANCE'] : null,
                                'applicable_from' => $applicableFrom,
                                'ledger_gst_registration_type' => $ledgerData['LEDGSTREGDETAILS.LIST']['GSTREGISTRATIONTYPE'] ?? null,
                                'gst_in' => $gst_in,
                                'email' => $email,
                                'phone_number' => substr($ledgerData['LEDGERMOBILE'] ?? null, 0, 20),
                                'mailing_applicable_from' => $mailingApplicableFrom,
                                'pincode' => $pincode,
                                'mailing_name' => html_entity_decode($ledgerData['LEDMAILINGDETAILS.LIST']['MAILINGNAME'] ?? null),
                                'address' => $addressList,
                                'state' => html_entity_decode($ledgerData['LEDMAILINGDETAILS.LIST']['STATE'] ?? null),
                                'country' => html_entity_decode($ledgerData['LEDMAILINGDETAILS.LIST']['COUNTRY'] ?? null),
                            ]
                        );
                        if ($tallyLedger) {
                            $ledgerCount++;
                        }
                    } catch (\Exception $e) {
                        // log in detail
                        Log::error('Error creating ledger record: ' . $e->getMessage(), [
                            'ledgerData' => $ledgerData,
                            'companyGuid' => $companyGuid,
                            'companyId' => $companyId,
                        ]);
                    }

                    /*if (!$tallyLedger) {
                        throw new \Exception('Failed to create or update tally ledger record.');
                    }*/
                }
            }

            return response()->json([
                'message' => 'Master Saved',
                'currencies_inserted' => $currencyCount,
                'groups_inserted' => $groupCount,
                'ledgers_inserted' => $ledgerCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error importing data: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
