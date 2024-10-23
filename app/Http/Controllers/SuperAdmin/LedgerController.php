<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\TallyLicense;
use App\Models\TallyCompany;
use App\Models\TallyLedgerGroup;
use App\Models\TallyLedger;
use App\Models\TallyItem;
use App\Models\TallyItemGroup;
use App\Models\TallyUnit;
use App\Models\TallyGodown;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherItem;
use App\Models\TallyBillAllocation;
use App\Models\TallyBatchAllocation;
use App\Models\TallyBankAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    private function ensureArray($data)
    {
        if (is_array($data)) {
            return $data;
        }

        return !empty($data) ? [$data] : [];
    }

    private function convertToDesiredDateFormat($date)
    {
        if (preg_match('/^\d{8}$/', $date)) {
            $dateObject = \DateTime::createFromFormat('Ymd', $date);
            return $dateObject ? $dateObject->format('d-M-y') : $date; // Example: '25-Aug-24'
        }
        return $date;
    }

    private function findTallyMessage($jsonArray, $path = '') 
    {
        foreach ($jsonArray as $key => $value) {
            $currentPath = $path ? $path . '.' . $key : $key;
            if ($key === 'TALLYMESSAGE') {
                return ['path' => $currentPath, 'value' => $value];
            }
            if (is_array($value)) {
                $result = $this->findTallyMessage($value, $currentPath);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        return null;
    }

    private function validateLicenseNumber(Request $request)
    {
        $licenseNumber = $request->input('license_number');
        if (empty($licenseNumber)) {
            Log::info('Please enter your license number');
            throw new \Exception('Please enter your license number');
        }

        $license = TallyLicense::where('license_number', $licenseNumber)->first();
        if (!$license) {
            Log::info('License not found for license number: ' . $licenseNumber);
            throw new \Exception('License not found');
        } elseif ($license->status != 'Active') {
            Log::info('License not active for license number: ' . $licenseNumber);
            throw new \Exception('License not active');
        }
    }

    private function licenseCheckJsonImport(Request $request)
    {
        $request->validate([
            'license_number' => 'required|string',
        ]);

        $licenseNumber = $request->input('license_number');

        $license = TallyLicense::where('license_number', $licenseNumber)->first();

        if (!$license) {
            return response()->json(['error' => 'License not found'], 404);
        } elseif ($license->status != 'Active') {
            return response()->json(['error' => 'License not active'], 403);
        }

        return response()->json(['message' => 'License is valid and active'], 200);
    }

    public function companyJsonImport(Request $request)
    {
        try {
            $this->validateLicenseNumber($request);
            
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
            $result = $this->findTallyMessage($data);
    
            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }
    
            $messagesPath = $result['path'];
            $messages = $result['value'];
    
            $companyGuids = TallyCompany::pluck('company_guid')->toArray();
            Log::info('Company GUIDs in Database:', ['companyGuids' => $companyGuids]);
    
            foreach ($messages as $message) {
                if (isset($message['COMPANY'])) {
                    $companyData = $message['COMPANY']['REMOTECMPINFO.LIST'];
                    $companyGuid = $companyData['NAME'];
    
                    if (!in_array($companyGuid, $companyGuids)) {
                        $company = TallyCompany::create([
                            'company_guid' => $companyGuid,
                            'company_name' => $companyData['REMOTECMPNAME'] ?? null,
                            'state' => $companyData['REMOTECMPSTATE'] ?? null,
                        ]);
    
                        if (!$company) {
                            throw new \Exception('Failed to create tally company record.');
                        }
    
                        $companyGuids[] = $companyGuid;
                    }
                }
            }
    
            return response()->json(['message' => 'Tally data saved successfully.', 'path' => $messagesPath]);
    
        } catch (\Exception $e) {
            Log::error('Error importing data: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function masterJsonImport(Request $request)
    {
        try {

            $this->validateLicenseNumber($request);

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
            $result = $this->findTallyMessage($data);

            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }

            $messagesPath = $result['path'];
            $messages = $result['value'];

            foreach ($messages as $message) {
                if (isset($message['GROUP'])) {
                    $groupData = $message['GROUP'];
                    Log::info('Group Data:', ['groupData' => $groupData]);


                    // Convert array fields to strings
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

                    $tallyLedgerGroup = TallyLedgerGroup::updateOrCreate(
                        ['ledger_group_guid' => $guid],
                        [
                            'company_id' => $companyId,
                            'parent' => $groupData['PARENT'] ?? null,
                            'affects_stock' => isset($groupData['AFFECTSSTOCK']) && $groupData['AFFECTSSTOCK'] === 'Yes',
                            'alter_id' => $groupData['ALTERID'] ?? null,
                            'ledger_group_name' => $nameField,
                        ]
                    );

                    if (!$tallyLedgerGroup) {
                        throw new \Exception('Failed to create or update tally ledger group record.');
                    }
                }
            }

            foreach ($messages as $message) {
                if (isset($message['LEDGER'])) {
                    $ledgerData = $message['LEDGER'];
                    Log::info('Ledger Data:', ['ledgerData' => $ledgerData]);

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
                    $ledgerGroup = TallyLedgerGroup::where('ledger_group_name', $parent)->first();
                    $ledgerGroupId = $ledgerGroup ? $ledgerGroup->ledger_group_id : null;

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
                            'party_gst_in' => $ledgerData['PARTYGSTIN'] ?? null,
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
                            'gst_in' => $ledgerData['LEDGSTREGDETAILS.LIST']['GSTIN'] ?? null,
                            'email' => $ledgerData['EMAIL'] ?? null,
                            'phone_number' => substr($ledgerData['LEDGERMOBILE'] ?? null, 0, 20),
                            'mailing_applicable_from' => $mailingApplicableFrom,
                            'pincode' => $ledgerData['LEDMAILINGDETAILS.LIST']['PINCODE'] ?? null,
                            'mailing_name' => html_entity_decode($ledgerData['LEDMAILINGDETAILS.LIST']['MAILINGNAME'] ?? null),
                            'address' => $addressList,
                            'state' => html_entity_decode($ledgerData['LEDMAILINGDETAILS.LIST']['STATE'] ?? null),
                            'country' => html_entity_decode($ledgerData['LEDMAILINGDETAILS.LIST']['COUNTRY'] ?? null),
                        ]
                    );

                    if (!$tallyLedger) {
                        throw new \Exception('Failed to create or update tally ledger record.');
                    }
                }
            }

            return response()->json(['message' => 'Tally data saved successfully.', 'path' => $messagesPath]);

        } catch (\Exception $e) {
            Log::error('Error importing data: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function stockItemJsonImport(Request $request)
    {
        try {

            $this->validateLicenseNumber($request);

            $jsonData = null;
            $fileName = 'tally_stock_item_data_' . date('YmdHis') . '.json';

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

            $result = $this->findTallyMessage($data);

            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }

            $messagesPath = $result['path'];
            $messages = $result['value'];

            $unitIds = [];

            foreach ($messages as $message) {
                if (isset($message['UNIT'])) {
                    $unitData = $message['UNIT'];
                    Log::info('Unit Data:', ['unitData' => $unitData]);

                    $reportingUQCDetails = $unitData['REPORTINGUQCDETAILS.LIST'] ?? [];
                    $reportingUQCName = $reportingUQCDetails['REPORTINGUQCNAME'] ?? null;
                    $applicableFrom = $reportingUQCDetails['APPLICABLEFROM'] ?? null;

                    $name = is_array($unitData['NAME']) ? $unitData['NAME'][0] : $unitData['NAME'];

                    $guid = $unitData['GUID'] ?? null;
                    $companyGuid = substr($guid, 0, 36);

                    $company = TallyCompany::where('company_guid', $companyGuid)->first();

                    if (!$company) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue; 
                    }

                    $companyId = $company->company_id;

                    $tallyUnit = TallyUnit::updateOrCreate(
                        ['unit_guid' => $unitData['GUID'] ?? null],
                        [
                            'company_id' => $companyId,
                            'alter_id' => $unitData['ALTERID'] ?? null,
                            'unit_name' => $name,
                            'is_gst_excluded' => ($unitData['ISGSTEXCLUDED'] ?? null) === 'Yes' ? true : false,
                            'is_simple_unit' => ($unitData['ISSIMPLEUNIT'] ?? null) === 'Yes' ? true : false,
                            'reporting_uqc_name' => $reportingUQCName,
                            'applicable_from' => $applicableFrom,
                        ]
                    );

                    if (!$tallyUnit) {
                        throw new \Exception('Failed to create or update tally unit record.');
                    }
                    $unitIds[$name] = $tallyUnit->unit_id;
                }
            }

            foreach ($messages as $message) {
                if (isset($message['GODOWN'])) {
                    $godownData = $message['GODOWN'];
                    Log::info('Godown Data:', ['godownData' => $godownData]);

                    $nameField = $godownData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? null;
                    if (is_array($nameField)) {
                        $nameField = implode(', ', $nameField);
                    }

                    $tallyGodown = TallyGodown::updateOrCreate(
                        ['godown_guid' => $godownData['GUID'] ?? null],
                        [
                            'parent' => $godownData['PARENT'] ?? null,
                            'alter_id' => $godownData['ALTERID'] ?? null,
                            'godown_name' => $nameField,
                        ]
                    );

                    if (!$tallyGodown) {
                        throw new \Exception('Failed to create or update tally Godown record.');
                    }
                }
            }

            foreach ($messages as $message) {
                if (isset($message['STOCKGROUP'])) {
                    $stockGroupData = $message['STOCKGROUP'];
                    Log::info('STOCKGROUP Data:', ['stockGroupData' => $stockGroupData]);

                    $nameField = $stockGroupData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? null;
                    if (is_array($nameField)) {
                        $nameField = implode(', ', $nameField);
                    }
                    
                    $guid = $stockGroupData['GUID'] ?? null;
                    $companyGuid = substr($guid, 0, 36);

                    $company = TallyCompany::where('company_guid', $companyGuid)->first();

                    if (!$company) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue; 
                    }

                    $companyId = $company->company_id;

                    $tallystockGroup = TallyItemGroup::updateOrCreate(
                        ['item_group_guid' => $stockGroupData['GUID'] ?? null],
                        [
                            'company_id' => $companyId,
                            'parent' => $stockGroupData['PARENT'] ?? null,
                            'alter_id' => $stockGroupData['ALTERID'] ?? null,
                            'item_group_name' => $nameField,
                        ]
                    );

                    if (!$tallystockGroup) {
                        throw new \Exception('Failed to create or update tally Stock Group record.');
                    }
                }
            }

            foreach ($messages as $message) {
                if (isset($message['STOCKITEM'])) {
                    $stockItemData = $message['STOCKITEM'];
                    Log::info('Stock Item Data:', ['stockItemData' => $stockItemData]);

                    $nameField = $stockItemData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? [];
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

                    $igstRate = null;
                    if (isset($stockItemData['GSTDETAILS.LIST']['STATEWISEDETAILS.LIST']['RATEDETAILS.LIST'])) {
                        foreach ($stockItemData['GSTDETAILS.LIST']['STATEWISEDETAILS.LIST']['RATEDETAILS.LIST'] as $rateDetail) {
                            if ($rateDetail['GSTRATEDUTYHEAD'] === 'IGST') {
                                $igstRate = $rateDetail['GSTRATE'] ?? null;
                                break;
                            }
                        }
                    }

                    $rateString = $stockItemData['OPENINGRATE'] ?? null;
                    $rate = $unit = null;

                    if ($rateString) {
                        $parts = explode('/', $rateString);
                        if (count($parts) === 2) {
                            $rate = trim($parts[0]);
                            $unit = trim($parts[1]);
                        } else {
                            $rate = $rateString;
                        }
                    }

                    $guid = $stockItemData['GUID'] ?? null;
                    $companyGuid = substr($guid, 0, 36);

                    $company = TallyCompany::where('company_guid', $companyGuid)->first();

                    if (!$company) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue; 
                    }

                    $companyId = $company->company_id;

                    $itemName = $stockItemData['NAME'] ?? null;
                    $stockGroup = TallyLedgerGroup::where('ledger_group_name', $itemName)->first();
                    $stockGroupIds = $stockGroup ? $stockGroup->ledger_group_id : null;


                    $unitName = $stockItemData['BASEUNITS'] ?? null;
                    $unitId = TallyUnit::where('unit_name', $unitName)->first();
                    $unitIds = $unitId ? $unitId->unit_id : null;

                    $tallyStockItem = TallyItem::updateOrCreate(
                        ['item_guid' => $stockItemData['GUID'] ?? null],
                        [
                            'company_id' => $companyId,
                            'item_group_id' => $stockGroupIds,
                            'unit_id' => $unitIds,
                            'item_name' => $stockItemData['NAME'] ?? null,
                            'parent' => $stockItemData['PARENT'] ?? null,
                            'category' => $stockItemData['CATEGORY'] ?? null,
                            'gst_applicable' => isset($stockItemData['GSTAPPLICABLE']) && $stockItemData['GSTAPPLICABLE'] === 'Yes',
                            'vat_applicable' => isset($stockItemData['VATAPPLICABLE']) && $stockItemData['VATAPPLICABLE'] === 'Yes',
                            'tax_classification_name' => $stockItemData['TAXCLASSIFICATIONNAME'] ?? null,
                            'gst_type_of_supply' => $stockItemData['GSTTYPEOFSUPPLY'] ?? null,
                            'excise_applicability' => $stockItemData['EXCISEAPPLICABILITY'] ?? null,
                            'sales_tax_cess_applicable' => $stockItemData['SALESTAXCESSAPPLICABLE'] ?? null,
                            'costing_method' => $stockItemData['COSTINGMETHOD'] ?? null,
                            'valuation_method' => $stockItemData['VALUATIONMETHOD'] ?? null,
                            'additional_units' => $stockItemData['ADDITIONALUNITS'] ?? null,
                            'excise_item_classification' => $stockItemData['EXCISEITEMCLASSIFICATION'] ?? null,
                            'vat_base_unit' => $stockItemData['VATBASEUNIT'] ?? null,
                            'is_cost_centres_on' => isset($stockItemData['ISCOSTCENTRESON']) && $stockItemData['ISCOSTCENTRESON'] === 'Yes',
                            'is_batch_wise_on' => isset($stockItemData['ISBATCHWISEON']) && $stockItemData['ISBATCHWISEON'] === 'Yes',
                            'is_perishable_on' => isset($stockItemData['ISPERISHABLEON']) && $stockItemData['ISPERISHABLEON'] === 'Yes',
                            'is_entry_tax_applicable' => isset($stockItemData['ISENTRYTAXAPPLICABLE']) && $stockItemData['ISENTRYTAXAPPLICABLE'] === 'Yes',
                            'is_cost_tracking_on' => isset($stockItemData['ISCOSTTRACKINGON']) && $stockItemData['ISCOSTTRACKINGON'] === 'Yes',
                            'is_updating_target_id' => isset($stockItemData['ISUPDATINGTARGETID']) && $stockItemData['ISUPDATINGTARGETID'] === 'Yes',
                            'is_security_on_when_entered' => isset($stockItemData['ISSECURITYONWHENENTERED']) && $stockItemData['ISSECURITYONWHENENTERED'] === 'Yes',
                            'as_original' => isset($stockItemData['ASORIGINAL']) && $stockItemData['ASORIGINAL'] === 'Yes',
                            'is_rate_inclusive_vat' => isset($stockItemData['ISRATEINCLUSIVEVAT']) && $stockItemData['ISRATEINCLUSIVEVAT'] === 'Yes',
                            'ignore_physical_difference' => isset($stockItemData['IGNOREPHYSICALDIFFERENCE']) && $stockItemData['IGNOREPHYSICALDIFFERENCE'] === 'Yes',
                            'ignore_negative_stock' => isset($stockItemData['IGNORENEGATIVESTOCK']) && $stockItemData['IGNORENEGATIVESTOCK'] === 'Yes',
                            'treat_sales_as_manufactured' => isset($stockItemData['TREATSALESASMANUFACTURED']) && $stockItemData['TREATSALESASMANUFACTURED'] === 'Yes',
                            'treat_purchases_as_consumed' => isset($stockItemData['TREATPURCHASESASCONSUMED']) && $stockItemData['TREATPURCHASESASCONSUMED'] === 'Yes',
                            'treat_rejects_as_scrap' => isset($stockItemData['TREATREJECTSASSCRAP']) && $stockItemData['TREATREJECTSASSCRAP'] === 'Yes',
                            'has_mfg_date' => isset($stockItemData['HASMFGDATE']) && $stockItemData['HASMFGDATE'] === 'Yes',
                            'allow_use_of_expired_items' => isset($stockItemData['ALLOWUSEOFEXPIREDITEMS']) && $stockItemData['ALLOWUSEOFEXPIREDITEMS'] === 'Yes',
                            'ignore_batches' => isset($stockItemData['IGNOREBATCHES']) && $stockItemData['IGNOREBATCHES'] === 'Yes',
                            'ignore_godowns' => isset($stockItemData['IGNOREGODOWNS']) && $stockItemData['IGNOREGODOWNS'] === 'Yes',
                            'adj_diff_in_first_sale_ledger' => isset($stockItemData['ADJDIFFINFIRSTSALELEDGER']) && $stockItemData['ADJDIFFINFIRSTSALELEDGER'] === 'Yes',
                            'adj_diff_in_first_purc_ledger' => isset($stockItemData['ADJDIFFINFIRSTPURCLEDGER']) && $stockItemData['ADJDIFFINFIRSTPURCLEDGER'] === 'Yes',
                            'cal_con_mrp' => isset($stockItemData['CALCONMRP']) && $stockItemData['CALCONMRP'] === 'Yes',
                            'exclude_jrnl_for_valuation' => isset($stockItemData['EXCLUDEJRNLFORVALUATION']) && $stockItemData['EXCLUDEJRNLFORVALUATION'] === 'Yes',
                            'is_mrp_incl_of_tax' => isset($stockItemData['ISMRPINCLOFTAX']) && $stockItemData['ISMRPINCLOFTAX'] === 'Yes',
                            'is_addl_tax_exempt' => isset($stockItemData['ISADDLTAXEXEMPT']) && $stockItemData['ISADDLTAXEXEMPT'] === 'Yes',
                            'is_supplementry_duty_on' => isset($stockItemData['ISSUPPLEMENTRYDUTYON']) && $stockItemData['ISSUPPLEMENTRYDUTYON'] === 'Yes',
                            'gvat_is_excise_appl' => isset($stockItemData['GVATISEXCISEAPPL']) && $stockItemData['GVATISEXCISEAPPL'] === 'Yes',
                            'is_additional_tax' => isset($stockItemData['ISADDITIONALTAX']) && $stockItemData['ISADDITIONALTAX'] === 'Yes',
                            'is_cess_exempted' => isset($stockItemData['ISCESSEXEMPTED']) && $stockItemData['ISCESSEXEMPTED'] === 'Yes',
                            'reorder_as_higher' => isset($stockItemData['REORDERASHIGHER']) && $stockItemData['REORDERASHIGHER'] === 'Yes',
                            'min_order_as_higher' => isset($stockItemData['MINORDERASHIGHER']) && $stockItemData['MINORDERASHIGHER'] === 'Yes',
                            'is_excise_calculate_on_mrp' => isset($stockItemData['ISEXCISECALCULATEONMRP']) && $stockItemData['ISEXCISECALCULATEONMRP'] === 'Yes',
                            'inclusive_tax' => isset($stockItemData['INCLUSIVETAX']) && $stockItemData['INCLUSIVETAX'] === 'Yes',
                            'gst_calc_slab_on_mrp' => isset($stockItemData['GSTCALCSLABONMRP']) && $stockItemData['GSTCALCSLABONMRP'] === 'Yes',
                            'modify_mrp_rate' => isset($stockItemData['MODIFYMRPRATE']) && $stockItemData['MODIFYMRPRATE'] === 'Yes',
                            'alter_id' => $stockItemData['ALTERID'] ?? null,
                            'denominator' => $stockItemData['DENOMINATOR'] ?? null,
                            'basic_rate_of_excise' => $stockItemData['BASICRATEOFEXCISE'] ?? null,
                            'base_units' => $stockItemData['BASEUNITS'] ?? null,
                            'opening_balance' => isset($stockItemData['OPENINGBALANCE']) ? preg_replace('/[^0-9.]/', '', $stockItemData['OPENINGBALANCE']) : null,
                            'opening_value' => $stockItemData['OPENINGVALUE'] ?? null,
                            'opening_rate' => isset($stockItemData['OPENINGRATE']) ? preg_replace('/[^0-9.]/', '', $stockItemData['OPENINGRATE']) : null,
                            // 'unit' => $unit,
                            'igst_rate' => $igstRate,
                            'hsn_code' => $stockItemData['HSNDETAILS.LIST']['HSNCODE'] ?? null,
                            'gst_details' => json_encode($stockItemData['GSTDETAILS.LIST'] ?? []),
                            'hsn_details' => json_encode($stockItemData['HSNDETAILS.LIST'] ?? []),
                            'alias1' => $alias1,
                            'alias2' => $alias2,
                            'alias3' => $alias3,
                            'batch_allocations' => json_encode($stockItemData['BATCHALLOCATIONS.LIST'] ?? []),
                        ]
                    );

                    if (!$tallyStockItem) {
                        throw new \Exception('Failed to create or update tally stock item record.');
                    }
                }
            }

            return response()->json(['message' => 'Tally data saved successfully.', 'path' => $messagesPath]);
        } catch (\Exception $e) {
            Log::error('Error importing stock items:', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function voucherJsonImport(Request $request)
    {
        try {

            $this->validateLicenseNumber($request);

            $jsonData = null;
            $fileName = 'tally_voucher_data_' . date('YmdHis') . '.json';

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

            $result = $this->findTallyMessage($data);

            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }

            $messagesPath = $result['path'];
            $messages = $result['value'];

            foreach ($messages as $message) {
                if (isset($message['VOUCHER'])) {
                    $voucherData = $message['VOUCHER'];
                    // Log::info('VOUCHER Data:', ['voucherData' => $voucherData]);

                    $guid = $voucherData['GUID'] ?? null;
                    $companyGuid = substr($guid, 0, 36);
                    $company = TallyCompany::where('company_guid', $companyGuid)->first();
                    if (!$company) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue; 
                    }
                    $companyId = $company->company_id;

                    $consigneeAddressList = $voucherData['BASICBUYERADDRESS.LIST']['BASICBUYERADDRESS'] ?? null;
                    if (is_array($consigneeAddressList)) {
                        $consigneeAddressList = implode(', ', $consigneeAddressList);
                    }

                    $buyerAddressList = $voucherData['ADDRESS.LIST']['ADDRESS'] ?? null;
                    if (is_array($buyerAddressList)) {
                        $buyerAddressList = implode(', ', $buyerAddressList);
                    }

                    $invoiceDelNotes = is_array($voucherData['INVOICEDELNOTES.LIST'] ?? null) ? $voucherData['INVOICEDELNOTES.LIST'] : [];
                    $deliveryNotes = [];
                    $formattedShippingDates = [];
                    foreach ($invoiceDelNotes as $note) {
                        if (isset($note['BASICSHIPDELIVERYNOTE']) && isset($note['BASICSHIPPINGDATE'])) {
                            $formattedDate = $this->convertToDesiredDateFormat($note['BASICSHIPPINGDATE']);
                            $formattedShippingDates[] = $formattedDate;
                            $deliveryNotes[] = $note['BASICSHIPDELIVERYNOTE'];
                        }
                    }
                    $deliveryNotesStr = implode(', ', $deliveryNotes);

                    $tallyVoucher = TallyVoucher::updateOrCreate([
                        'voucher_guid' => $voucherData['GUID'],
                        'company_id' => $companyId,
                        'voucher_type' => $voucherData['VOUCHERTYPENAME'] ?? null,
                        'is_cancelled' => isset($voucherData['ISCANCELLED']) && $voucherData['ISCANCELLED'] === 'Yes',
                        'is_optional' => isset($voucherData['ISOPTIONAL']) && $voucherData['ISOPTIONAL'] === 'Yes',
                        'alter_id' => $voucherData['ALTERID'] ?? null,
                        'voucher_number' => $voucherData['VOUCHERNUMBER'] ?? null,
                        'voucher_date' => $voucherData['DATE'] ?? null,
                        'reference_date' => !empty($voucherData['REFERENCEDATE']) ? $voucherData['REFERENCEDATE'] : null,
                        'reference_no' => $voucherData['REFERENCE'] ?? null,
                        'place_of_supply' => $voucherData['PLACEOFSUPPLY'] ?? null,
                        'country_of_residense' => $voucherData['COUNTRYOFRESIDENCE'] ?? null,
                        'gst_registration_type' => $voucherData['GSTREGISTRATIONTYPE'] ?? null,
                        'numbering_style' => $voucherData['NUMBERINGSTYLE'] ?? null,
                        'narration' => $voucherData['NARRATION'] ?? null,
                        'order_no' => $voucherData['INVOICEORDERLIST.LIST']['BASICPURCHASEORDERNO'] ?? null,
                        'order_date' => $voucherData['INVOICEORDERLIST.LIST']['BASICORDERDATE'] ?? null,
                        'ship_doc_no' => $voucherData['BASICSHIPDOCUMENTNO'] ?? null,
                        'ship_by' => $voucherData['BASICSHIPPEDBY'] ?? null,
                        'final_destination' => $voucherData['BASICFINALDESTINATION'] ?? null,
                        'bill_lading_no' => $voucherData['BILLOFLADINGNO'] ?? null,
                        'bill_lading_date' => !empty($voucherData['BILLOFLADINGDATE']) ? $voucherData['BILLOFLADINGDATE'] : null,
                        'vehicle_no' => $voucherData['BASICSHIPVESSELNO'] ?? null,
                        'terms' => is_array($voucherData['BASICORDERTERMS.LIST']['BASICORDERTERMS'] ?? null) 
                                    ? implode(', ', $voucherData['BASICORDERTERMS.LIST']['BASICORDERTERMS']) 
                                    : ($voucherData['BASICORDERTERMS.LIST']['BASICORDERTERMS'] ?? null),
                        'consignee_name' => $voucherData['BASICBUYERNAME'] ?? null,
                        'consignee_state_name' => $voucherData['CONSIGNEESTATENAME'] ?? null,
                        'consignee_gstin' => $voucherData['CONSIGNEEGSTIN'] ?? null,
                        'consignee_addr' => $consigneeAddressList,
                        'buyer_name' => $voucherData['BASICBUYERNAME'] ?? null,
                        'buyer_addr' => $buyerAddressList,
                        'delivery_notes' => $deliveryNotesStr,
                        'delivery_dates' => json_encode($formattedShippingDates),
                        'due_date_payment' => $voucherData['BASICDUEDATEOFPYMT'] ?? null,
                        'buyer_gstin' => $voucherData['PARTYGSTIN'] ?? null,
                        'order_ref' => $voucherData['BASICORDERREF'] ?? null,
                        'cost_center_name' => $voucherData['COSTCENTRENAME'] ?? null,
                        'cost_center_amount' => $voucherData['COSTCENTREAMOUNT'] ?? null,
                    ]);


                    if (!$tallyVoucher) {
                        throw new \Exception('Failed to create or update tally Voucher record.');
                    }

                    $this->processLedgerEntries($voucherData, $tallyVoucher);
                }
            }

            return response()->json(['message' => 'Tally data saved successfully.', 'path' => $messagesPath]);
        } catch (\Exception $e) {
            Log::error('Error saving Tally voucher data:', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'Failed to save Tally data', 'message' => $e->getMessage()], 500);
        }
    }

    private function processLedgerEntries(array $voucherData, TallyVoucher $tallyVoucher)
    {
        if (isset($voucherData['LEDGERENTRIES.LIST'])) {
            $ledgerEntries = $this->ensureArray($voucherData['LEDGERENTRIES.LIST']);
    
            foreach ($ledgerEntries as $ledgerEntry) {
                $ledgerHeadData = [
                    'voucher_id'   => $tallyVoucher->voucher_id,
                    'entry_type'   => 'LeDebit',
                    'amount'       => $ledgerEntry['AMOUNT'] ?? null,
                ];
    
                try {
                    if (TallyVoucherHead::updateOrCreate($ledgerHeadData) === false) {
                        Log::error('Error saving Tally voucher data:', ['data' => $ledgerHeadData]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception while saving Tally voucher data:', [
                        'exception' => $e->getMessage(),
                        'data' => $ledgerHeadData
                    ]);
                }
            }
        }
    
        if (isset($voucherData['ALLLEDGERENTRIES.LIST'])) {
            $allLedgerEntries = $this->ensureArray($voucherData['ALLLEDGERENTRIES.LIST']);
    
            foreach ($allLedgerEntries as $allLedgerEntry) {
                $allLedgerHeadData = [
                    'voucher_id'   => $tallyVoucher->voucher_id,
                    'entry_type'   => 'AllDebit',
                    'amount'       => $allLedgerEntry['AMOUNT'] ?? null,
                ];
    
                try {
                    if (TallyVoucherHead::updateOrCreate($allLedgerHeadData) === false) {
                        Log::error('Error saving ALLLEDGERENTRIES data:', ['data' => $allLedgerHeadData]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception while saving ALLLEDGERENTRIES data:', [
                        'exception' => $e->getMessage(),
                        'data' => $allLedgerHeadData
                    ]);
                }
            }
        }
    
        if (isset($voucherData['ALLINVENTORYENTRIES.LIST'])) {
            $inventoryEntries = $this->ensureArray($voucherData['ALLINVENTORYENTRIES.LIST']);
    
            foreach ($inventoryEntries as $inventoryEntry) {
                if (is_array($inventoryEntry) && isset($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST']) && is_array($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST'])) {
                    $allocations = $this->ensureArray($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST']);
    
                    foreach ($allocations as $allocation) {
                        $allocationHeadData = [
                            'voucher_id'   => $tallyVoucher->voucher_id,
                            'entry_type'   => 'AccDebit',
                            'amount'       => $allocation['AMOUNT'] ?? null,
                        ];
    
                        try {
                            if (TallyVoucherHead::updateOrCreate($allocationHeadData) === false) {
                                Log::error('Error saving ACCOUNTINGALLOCATIONS data:', ['data' => $allocationHeadData]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Exception while saving ACCOUNTINGALLOCATIONS data:', [
                                'exception' => $e->getMessage(),
                                'data' => $allocationHeadData
                            ]);
                        }
                    }
                } else {
                    Log::warning('ACCOUNTINGALLOCATIONS.LIST not found or it is not an array.', [
                        'inventory_entry' => is_array($inventoryEntry) ? json_encode($inventoryEntry) : $inventoryEntry
                    ]);
                }
            }
        }
    }
    

    // private function processLedgerEntries(array $voucherData, TallyVoucher $tallyVoucher)
    // {
    //     if (isset($voucherData['LEDGERENTRIES.LIST'])) {
    //         $ledgerEntries = $this->ensureArray($voucherData['LEDGERENTRIES.LIST']);

    //         foreach ($ledgerEntries as $ledgerEntry) {
    //             $ledgerHeadData = [
    //                 'voucher_id'   => $tallyVoucher->voucher_id,
    //                 'entry_type'   => 'LeDebit',
    //                 // 'ledger_name'  => $ledgerEntry['LEDGERNAME'] ?? null,
    //                 'amount'       => $ledgerEntry['AMOUNT'] ?? null,
    //             ];

    //             TallyVoucherHead::create($ledgerHeadData);
    //         }
    //     }

    //     if (isset($voucherData['ALLLEDGERENTRIES.LIST'])) {
    //         $allLedgerEntries = $this->ensureArray($voucherData['ALLLEDGERENTRIES.LIST']);

    //         foreach ($allLedgerEntries as $allLedgerEntry) {
    //             $allLedgerEntries = [
    //                 'voucher_id'   => $tallyVoucher->voucher_id,
    //                 'entry_type'   => 'AllDebit',
    //                 // 'ledger_name'  => $allLedgerEntry['LEDGERNAME'] ?? null,
    //                 'amount'       => $allLedgerEntry['AMOUNT'] ?? null,
    //             ];

    //             TallyVoucherHead::create($allLedgerEntries);
    //         }
    //     }


    //     if (isset($voucherData['ALLLEDGERENTRIES.LIST'])) {
    //         $allLedgerEntries = $this->ensureArray($voucherData['ALLLEDGERENTRIES.LIST']);

    //         foreach ($allLedgerEntries as $allLedgerEntry) {
    //             $allLedgerHeadData = [
    //                 'voucher_id'   => $tallyVoucher->voucher_id,
    //                 'entry_type'   => 'AllDebit',
    //                 // 'ledger_name'  => $allLedgerEntry['LEDGERNAME'] ?? null,
    //                 'amount'       => $allLedgerEntry['AMOUNT'] ?? null,
    //             ];

    //                     TallyVoucherHead::create($allLedgerHeadData);

    //             if (isset($allLedgerEntry['ACCOUNTINGALLOCATIONS.LIST'])) {
    //                 $allocations = $this->ensureArray($allLedgerEntry['ACCOUNTINGALLOCATIONS.LIST']);

    //                 foreach ($allocations as $allocation) {
    //                     $allocationHeadData = [
    //                         'voucher_id'   => $tallyVoucher->voucher_id,
    //                         'entry_type'   => 'AccDebit',
    //                         // 'ledger_name'  => $allocation['LEDGERNAME'] ?? null,
    //                         'amount'       => $allocation['AMOUNT'] ?? null,
    //                     ];

    //                     TallyVoucherHead::create($allocationHeadData);
    //                 }
    //             }
    //         }
    //     }
    // }


}
