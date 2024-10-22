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

class LedgerController extends Controller
{
    private function ensureArray($data)
    {
        return is_array($data) ? $data : [$data];
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
    
            $companyGuids = TallyCompany::pluck('guid')->toArray();
            Log::info('Company GUIDs in Database:', ['companyGuids' => $companyGuids]);
    
            foreach ($messages as $message) {
                if (isset($message['COMPANY'])) {
                    $companyData = $message['COMPANY']['REMOTECMPINFO.LIST'];
                    $companyGuid = $companyData['NAME'];
    
                    if (!in_array($companyGuid, $companyGuids)) {
                        $company = TallyCompany::create([
                            'guid' => $companyGuid,
                            'name' => $companyData['REMOTECMPNAME'] ?? null,
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

            $ledgerGroupGuid = [];
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
    
                    $companyExists = \DB::table('tally_companies')->where('guid', $companyGuid)->exists();
    
                    if (!$companyExists) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue; // Skip this record
                    }
                    

                    $tallyLedgerGroup = TallyLedgerGroup::updateOrCreate(
                        ['guid' => $guid],
                        [
                            'company_guid' => $companyGuid,
                            'parent' => $groupData['PARENT'] ?? null,
                            'affects_stock' => isset($groupData['AFFECTSSTOCK']) && $groupData['AFFECTSSTOCK'] === 'Yes',
                            'alter_id' => $groupData['ALTERID'] ?? null,
                            'name' => $nameField,
                        ]
                    );

                    if (!$tallyLedgerGroup) {
                        throw new \Exception('Failed to create or update tally ledger group record.');
                    }
                    $ledgerGroupGuid = $tallyLedgerGroup->guid;
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
            

                    $tallyLedger = TallyLedger::updateOrCreate(
                        ['guid' => $guid],
                        [
                            'company_guid' => $companyGuid,
                            'parent' => $ledgerData['PARENT'] ?? null,
                            'ledger_group_guid' => $ledgerGroupGuid,
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
                            'alter_id' => $ledgerData['ALTERID'] ?? null,
                            'opening_balance' => !empty($ledgerData['OPENINGBALANCE']) ? $ledgerData['OPENINGBALANCE'] : null,
                            'name' => $languageName,
                            'alias1' => $alias1,
                            'alias2' => $alias2,
                            'alias3' => $alias3,
                            'applicable_from' => $applicableFrom,
                            'ledger_gst_registration_type' => $ledgerData['LEDGSTREGDETAILS.LIST']['GSTREGISTRATIONTYPE'] ?? null,
                            'gst_in' => $ledgerData['LEDGSTREGDETAILS.LIST']['GSTIN'] ?? null,
                            'email' => $ledgerData['EMAIL'] ?? null,
                            'phone_no' => substr($ledgerData['LEDGERMOBILE'] ?? null, 0, 20),
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

            $unitGuids = [];

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

                    $tallyUnit = TallyUnit::updateOrCreate(
                        ['guid' => $unitData['GUID'] ?? null],
                        [
                            'company_guid' => $companyGuid,
                            'name' => $name,
                            'is_gst_excluded' => ($unitData['ISGSTEXCLUDED'] ?? null) === 'Yes' ? true : false,
                            'is_simple_unit' => ($unitData['ISSIMPLEUNIT'] ?? null) === 'Yes' ? true : false,
                            'alter_id' => $unitData['ALTERID'] ?? null,
                            'reporting_uqc_name' => $reportingUQCName,
                            'applicable_from' => $applicableFrom,
                        ]
                    );

                    if (!$tallyUnit) {
                        throw new \Exception('Failed to create or update tally unit record.');
                    }
                    $unitGuids[$name] = $tallyUnit->guid;
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
                        ['guid' => $godownData['GUID'] ?? null],
                        [
                            'parent' => $godownData['PARENT'] ?? null,
                            'alter_id' => $godownData['ALTERID'] ?? null,
                            'name' => $nameField,
                        ]
                    );

                    if (!$tallyGodown) {
                        throw new \Exception('Failed to create or update tally Godown record.');
                    }
                }
            }

            $stockGroupGuid = [];
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
    
                    // Check if the company exists in the tally_companies table
                    $companyExists = \DB::table('tally_companies')->where('guid', $companyGuid)->exists();
    
                    if (!$companyExists) {
                        // Handle case when the company does not exist, log it or throw an exception
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        throw new \Exception('Company GUID not found in tally_companies: ' . $companyGuid);
                    }

                    $tallystockGroup = TallyItemGroup::updateOrCreate(
                        ['guid' => $stockGroupData['GUID'] ?? null],
                        [
                            'company_guid' => $companyGuid,
                            'parent' => $stockGroupData['PARENT'] ?? null,
                            'alter_id' => $stockGroupData['ALTERID'] ?? null,
                            'name' => $nameField,
                        ]
                    );

                    if (!$tallystockGroup) {
                        throw new \Exception('Failed to create or update tally Stock Group record.');
                    }
                    $stockGroupGuid = $tallystockGroup->guid;
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

                    $tallyUnitGuid = $unitGuids[$stockItemData['BASEUNITS'] ?? null] ?? null;

                    $tallyStockItem = TallyItem::updateOrCreate(
                        ['guid' => $stockItemData['GUID'] ?? null],
                        [
                            'company_guid' => $companyGuid,
                            'item_group_guid' => $stockGroupGuid,
                            'name' => $stockItemData['NAME'] ?? null,
                            'parent' => $stockItemData['PARENT'] ?? null,
                            'category' => $stockItemData['CATEGORY'] ?? null,
                            'gst_applicable' => $stockItemData['GSTAPPLICABLE'] ?? null,
                            'tax_classification_name' => $stockItemData['TAXCLASSIFICATIONNAME'] ?? null,
                            'gst_type_of_supply' => $stockItemData['GSTTYPEOFSUPPLY'] ?? null,
                            'excise_applicability' => $stockItemData['EXCISEAPPLICABILITY'] ?? null,
                            'sales_tax_cess_applicable' => $stockItemData['SALESTAXCESSAPPLICABLE'] ?? null,
                            'vat_applicable' => $stockItemData['VATAPPLICABLE'] ?? null,
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
                            'rate_of_vat' => $stockItemData['RATEOFVAT'] ?? null,
                            'vat_base_no' => $stockItemData['VATBASENO'] ?? null,
                            'vat_trail_no' => $stockItemData['VATTRAILNO'] ?? null,
                            'vat_actual_ratio' => $stockItemData['VATACTUALRATIO'] ?? null,
                            'tally_unit_guid' => $tallyUnitGuid,
                            'base_units' => $stockItemData['BASEUNITS'] ?? null,
                            'opening_balance' => isset($stockItemData['OPENINGBALANCE']) ? preg_replace('/[^0-9.]/', '', $stockItemData['OPENINGBALANCE']) : null,
                            'opening_value' => $stockItemData['OPENINGVALUE'] ?? null,
                            'opening_rate' => isset($stockItemData['OPENINGRATE']) ? preg_replace('/[^0-9.]/', '', $stockItemData['OPENINGRATE']) : null,
                            'unit' => $unit,
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
            $fileName = 'tally_voucher_data_' . now()->format('YmdHis') . '.json';

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

            $ledgerHeadMap = [];

            foreach ($messages as $message) {
                if (isset($message['VOUCHER'])) {
                    $voucherData = $message['VOUCHER'];
                    $partyLedgerName = $voucherData['PARTYLEDGERNAME'] ?? $voucherData['PARTYNAME'] ?? null;
                    $companyGuid = substr($voucherData['GUID'], 0, 36);

                    $ledgerEntries = $this->normalizeEntries($this->ensureArray($voucherData['LEDGERENTRIES.LIST'] ?? []));
                    $allLedgerEntries = $this->normalizeEntries($this->ensureArray($voucherData['ALLLEDGERENTRIES.LIST'] ?? []));
                    $inventoryEntries = $this->normalizeEntries($this->ensureArray($voucherData['ALLINVENTORYENTRIES.LIST'] ?? []));

                    $accountingAllocations = [];
                    foreach ($inventoryEntries as $inventoryEntry) {
                        if (isset($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST'])) {
                            $accountingAllocations = array_merge($accountingAllocations, $this->normalizeEntries($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST']));
                        }
                    }


                    $combinedLedgerEntries = array_merge($ledgerEntries, $allLedgerEntries);

                    $inventoryEntries = $this->normalizeEntries($voucherData['ALLINVENTORYENTRIES.LIST'] ?? []);
                    $billAllocations = [];
                    foreach ($combinedLedgerEntries as $ledgerEntry) {
                        if (isset($ledgerEntry['BILLALLOCATIONS.LIST'])) {
                            $billAllocations[$ledgerEntry['LEDGERNAME']] = $this->normalizeEntries($ledgerEntry['BILLALLOCATIONS.LIST']);
                        }
                    }

                    $bankAllocations = [];
                    foreach ($combinedLedgerEntries as $ledgerEntry) {
                        if (isset($ledgerEntry['BANKALLOCATIONS.LIST'])) {
                            $bankAllocations[$ledgerEntry['LEDGERNAME']] = $this->normalizeEntries($ledgerEntry['BANKALLOCATIONS.LIST']);
                        }
                    }

                    $batchAllocations = [];
                    foreach ($inventoryEntries as $inventoryEntry) {
                        if (isset($inventoryEntry['BATCHALLOCATIONS.LIST'])) {
                            $batchAllocations[$inventoryEntry['STOCKITEMNAME']] = $this->normalizeEntries($inventoryEntry['BATCHALLOCATIONS.LIST']);
                        }
                    }


                    $combinedLedgerEntries = $this->processLedgerEntries($combinedLedgerEntries, $companyGuid);
                    $inventoryEntries = $this->processInventoryEntries($inventoryEntries, $companyGuid);
                    $accountingAllocations = $this->processAccountingAllocations($accountingAllocations, $companyGuid);


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


                    if (empty($partyLedgerName)) {
                        Log::error('Party Ledger Name is missing from voucher data:', $voucherData);
                        continue;
                    }

                    
                    $existingVoucher = TallyVoucher::where('guid', $voucherData['GUID'])
                                    ->where('company_guid', $companyGuid)
                                    ->first();

                    if ($existingVoucher) {
                        $voucherHeadIds = TallyVoucherHead::where('voucher_id', $existingVoucher->voucher_id)->pluck('voucher_head_id')->toArray();
                        $inventoryEntriesWithId = TallyVoucherItem::where('voucher_item_id', $existingVoucher->voucher_id)->pluck('voucher_item_id')->toArray();
                        $this->deleteRelatedVoucherData($existingVoucher->voucher_id, $voucherHeadIds, $inventoryEntriesWithId);
                        $existingVoucher->delete();
                    }

                    $tallyVoucher = TallyVoucher::create([
                        'guid' => $voucherData['GUID'],
                        'company_guid' => substr($voucherData['GUID'], 0, 36),
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

                    if ($tallyVoucher) {
                        $voucherHeadIds = $this->processAccountingAllocationForVoucher($tallyVoucher->voucher_id, $accountingAllocations);
                        $voucherHeadIds = $this->processLedgerEntriesForVoucher($tallyVoucher->voucher_id, $combinedLedgerEntries);
                        $inventoryEntriesWithId = $this->processInventoryEntriesForVoucher($tallyVoucher->voucher_id, $inventoryEntries, $companyGuid, $voucherHeadIds, $ledgerId);
                        $this->processBillAllocationsForVoucher($voucherHeadIds, $billAllocations);
                        $this->processBankAllocationsForVoucher($voucherHeadIds, $bankAllocations);
                        $this->processBatchAllocationsForVoucher($inventoryEntriesWithId, $batchAllocations, $companyGuid);
                    } else {
                        throw new \Exception('Failed to create or update voucher item record.');
                    }
                }
            }
            
            return response()->json(['message' => 'Tally data saved successfully.', 'path' => $messagesPath]);
        } catch (\Exception $e) {
            Log::error('Error saving Tally voucher data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to save Tally data: ' . $e->getMessage()], 500);
        }
    }

    private function deleteRelatedVoucherData($voucherId, $voucherHeadIds, $inventoryEntriesWithId)
    {
        TallyVoucherHead::where('voucher_id', $voucherId)->delete();
        TallyVoucherItem::where('voucher_head_id', $voucherHeadIds)->delete();
        TallyBillAllocation::where('voucher_head_id', $voucherHeadIds)->delete();
        TallyBankAllocation::where('voucher_head_id', $voucherHeadIds)->delete();
        TallyBatchAllocation::where('voucher_item_id', $inventoryEntriesWithId)->delete();
    }

    private function normalizeEntries($entries)
    {
        if (is_object($entries)) {
            $entries = (array) $entries;
        }

        if (is_array($entries)) {
            foreach ($entries as &$entry) {
                if (is_object($entry)) {
                    $entry = (array) $entry;
                }
            }
            return empty($entries) || isset($entries[0]) ? $entries : [$entries];
        }

        return [];
    }

    private function processLedgerEntries(array $entries, $companyGuid)
    {
        $ledgerEntries = [];

        foreach ($entries as $entry) {
            if (isset($entry['LEDGERNAME'], $entry['AMOUNT'])) {
                $ledgerName = htmlspecialchars_decode($entry['LEDGERNAME']);
                $amount = $entry['AMOUNT'];
                $entryType = $amount < 0 ? "debit" : "credit";

                // Retrieve the ledger GUID from the database
                $ledgerId = TallyLedger::where('name', $ledgerName)
                    ->where('company_guid', $companyGuid) // Ensure you are filtering by company GUID
                    ->value('ledger_id');

                if ($ledgerId) {
                    // Normalize GUIDs by extracting the base GUID part (before the first '-')
                    $normalizedLedgerId = explode('-', $ledgerId)[0];
                    $normalizedEntryGuid = explode('-', $companyGuid)[0];

                    // Compare the normalized GUID
                    if ($normalizedLedgerId === $normalizedEntryGuid) {
                        $ledgerEntries[] = [
                            // 'ledger_name' => $ledgerName,
                            'amount' => $amount,
                            'entry_type' => $entryType,
                            'ledger_id' => $ledgerId,
                            'is_deemed_positive' => isset($entry['ISDEEMEDPOSITIVE']) && $entry['ISDEEMEDPOSITIVE'] === 'Yes',
                        ];
                    } else {
                        // Log mismatch or handle the case
                        Log::error('GUID mismatch for ledger: ' . $ledgerName . '. Expected GUID: ' . $normalizedEntryGuid . ', Found GUID: ' . $normalizedLedgerId);
                    }
                } else {
                    // Handle case where GUID is not found in the database
                    Log::error('Ledger GUID not found in database for ledger: ' . $ledgerName);
                }
            } else {
                Log::error('Missing or invalid LEDGERNAME or AMOUNT in LEDGERENTRIES.LIST entry: ' . json_encode($entry));
            }
        }

        return $ledgerEntries;
    }

    private function processLedgerEntriesForVoucher($voucherId, array $entries)
    {
        $voucherHeadIds = [];
        foreach ($entries as $entry) {
            $voucherHead = TallyVoucherHead::updateOrCreate(
                [
                    'voucher_id' => $voucherId,
                    // 'ledger_name' => $entry['ledger_name'],
                ],
                [
                    'amount' => $entry['amount'],
                    'entry_type' => $entry['entry_type'],
                    'ledger_id' => $entry['ledger_id'],
                    'is_deemed_positive' => isset($entry['ISDEEMEDPOSITIVE']) && $entry['ISDEEMEDPOSITIVE'] === 'Yes',
        
                ]
            );
            $voucherHeadIds[] = [
                'id' => $voucherHead->id,
                // 'ledger_name' => $entry['ledger_name'],
            ];
        }
        return $voucherHeadIds;
    }

    private function processAccountingAllocations(array $entries, $companyGuid)
    {
        $ledgerEntries = [];

        foreach ($entries as $entry) {
            if (isset($entry['LEDGERNAME'], $entry['AMOUNT'])) {
                $ledgerName = htmlspecialchars_decode($entry['LEDGERNAME']);
                $amount = $entry['AMOUNT'];
                $entryType = $amount < 0 ? "debit" : "credit";

                // Retrieve the ledger GUID from the database
                $ledgerId = TallyLedger::where('name', $ledgerName)
                    ->where('company_guid', $companyGuid) // Ensure filtering by company GUID
                    ->value('guid');

                if ($ledgerId) {
                    // Normalize GUIDs by extracting the base GUID part (before the first '-')
                    $normalizedLedgerId = explode('-', $ledgerId)[0];
                    $normalizedEntryGuid = explode('-', $companyGuid)[0];

                    // Compare the normalized GUID
                    if ($normalizedLedgerId === $normalizedEntryGuid) {
                        // If the same ledger_name already exists, add the amount
                        if (isset($ledgerEntries[$ledgerName])) {
                            $ledgerEntries[$ledgerName]['amount'] += $amount;
                        } else {
                            $ledgerEntries[$ledgerName] = [
                                'ledger_name' => $ledgerName,
                                'amount' => $amount,
                                'entry_type' => $entryType,
                                'ledger_id' => $ledgerId,
                                'is_deemed_positive' => isset($entry['ISDEEMEDPOSITIVE']) && $entry['ISDEEMEDPOSITIVE'] === 'Yes',
        
                            ];
                        }
                    } else {
                        // Log mismatch or handle the case
                        Log::error('GUID mismatch for ledger: ' . $ledgerName . '. Expected GUID: ' . $normalizedEntryGuid . ', Found GUID: ' . $normalizedLedgerId);
                    }
                } else {
                    // Handle case where GUID is not found in the database
                    Log::error('Ledger GUID not found in database for ledger: ' . $ledgerName);
                }
            } else {
                Log::error('Missing or invalid LEDGERNAME or AMOUNT in LEDGERENTRIES.LIST entry: ' . json_encode($entry));
            }
        }

        // Convert associative array back to normal array
        return array_values($ledgerEntries);
    }

    private function processAccountingAllocationForVoucher($voucherId, array $entries)
    {
        $voucherHeadIds = [];

        foreach ($entries as $entry) {
            // Create a new record for each entry, allowing duplicates
            $voucherHead = TallyVoucherHead::create([
                'voucher_id' => $voucherId,
                // 'ledger_name' => $entry['ledger_name'],
                'amount' => $entry['amount'],
                'entry_type' => $entry['entry_type'],
                // 'ledger_guid' => $entry['ledger_guid'],
                'is_deemed_positive' => isset($entry['ISDEEMEDPOSITIVE']) && $entry['ISDEEMEDPOSITIVE'] === 'Yes',
        
            ]);

            $voucherHeadIds[] = [
                'id' => $voucherHead->id,
                // 'ledger_name' => $entry['ledger_name'],
            ];
        }

        return $voucherHeadIds;
    }

    private function processInventoryEntries(array $entries)
    {
        $inventoryEntries = [];
        foreach ($entries as $entry) {
            $rateString = $entry['RATE'] ?? null;
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

            $igstRate = null;
            if (isset($entry['RATEDETAILS.LIST'])) {
                foreach ($entry['RATEDETAILS.LIST'] as $rateDetail) {
                    if ($rateDetail['GSTRATEDUTYHEAD'] === 'IGST') {
                        $igstRate = $rateDetail['GSTRATE'] ?? null;
                        break;
                    }
                }
            }


            $billedQtyString = $entry['BILLEDQTY'] ?? null;
            $billed_qty = null;

            if ($billedQtyString) {
                if (preg_match('/\d+/', $billedQtyString, $matches)) {
                    $billed_qty = $matches[0]; 
                }
            }
            $billed_qty = is_numeric($billed_qty) ? (float) $billed_qty : null;

            $actualQtyString = $entry['ACTUALQTY'] ?? null;
            $actual_qty = null;

            if ($billedQtyString) {
                if (preg_match('/\d+/', $billedQtyString, $matches)) {
                    $actual_qty = $matches[0]; 
                }
            }
            $actual_qty = is_numeric($actual_qty) ? (float) $actual_qty : null;



            $inventoryEntries[] = [
                'stock_item_name' => $entry['STOCKITEMNAME'] ?? null,
                'gst_taxability' => $entry['GSTOVRDNTAXABILITY'] ?? null,
                'gst_source_type' => $entry['GSTSOURCETYPE'] ?? null,
                'gst_item_source' => $entry['GSTITEMSOURCE'] ?? null,
                'gst_ledger_source' => $entry['GSTLEDGERSOURCE'] ?? null,
                'hsn_source_type' => $entry['HSNSOURCETYPE'] ?? null,
                'hsn_item_source' => $entry['HSNLEDGERSOURCE'] ?? null,
                'gst_rate_infer_applicability' => $entry['GSTRATEINFERAPPLICABILITY'] ?? null,
                'gst_hsn_infer_applicability' => $entry['GSTHSNINFERAPPLICABILITY'] ?? null,
                'rate' => $rate,
                'unit' => $unit,
                'billed_qty' => $billed_qty,
                'actual_qty' => $actual_qty,
                'amount' => $entry['AMOUNT'] ?? null,
                'gst_hsn_name' => $entry['GSTHSNNAME'] ?? null,
                'discount' => $entry['DISCOUNT'] ?? null,
                'igst_rate' => $igstRate,
            ];
        }


        Log::info('Processed inventory entries: ', $inventoryEntries);
        return $inventoryEntries;
    }

    private function processInventoryEntriesForVoucher($voucherId, array $entries, $companyGuid, $voucherHeadIds)
    {
        $inventoryEntriesWithId = [];
        $voucherHeadIndex = 0;
        if (is_array($entries) || is_object($entries)) {
        foreach ($entries as $item) {
            
            $StockItemGuid = TallyItem::where('name', $item['stock_item_name'])
            ->where('company_guid', $companyGuid)
            ->value('guid');

            if (!empty($voucherHeadIds)) {
                $voucherHeadId = $voucherHeadIds[$voucherHeadIndex]['id'] ?? null;
                $voucherHeadIndex = ($voucherHeadIndex + 1) % count($voucherHeadIds);
            } else {
                $voucherHeadId = null;
                Log::error('voucherHeadIds is empty.');
            }


            $newEntry = TallyVoucherItem::create([
                // 'voucher_id' => $voucherId,
                'stock_item_name' => $item['stock_item_name'],
                'billed_qty' => $item['billed_qty'],
                'actual_qty' => $item['actual_qty'],
                'rate' => $item['rate'],
                'unit' => $item['unit'],
                'amount' => $item['amount'],
                'gst_taxability' => $item['gst_taxability'],
                'gst_source_type' => $item['gst_source_type'],
                'gst_item_source' => $item['gst_item_source'],
                'gst_ledger_source' => $item['gst_ledger_source'],
                'hsn_source_type' => $item['hsn_source_type'],
                'hsn_item_source' => $item['hsn_item_source'],
                'gst_rate_infer_applicability' => $item['gst_rate_infer_applicability'],
                'gst_hsn_infer_applicability' => $item['gst_hsn_infer_applicability'],
                'igst_rate' => $item['igst_rate'],
                'gst_hsn_name' => $item['gst_hsn_name'],
                'discount' => $item['discount'],
                'stock_item_guid' => $StockItemGuid,
                'company_guid' => $companyGuid,
                'voucher_head_id' => $voucherHeadId,
            ]);

            $inventoryEntriesWithId[] = [
                'id' => $newEntry->id,
                'stock_item_name' => $item['stock_item_name'],
            ];
        }
        }
        Log::info('Processed inventory entries: ', $inventoryEntriesWithId);
        return $inventoryEntriesWithId;
    }

    private function processBillAllocationsForVoucher($voucherHeadIds, array $billAllocations)
    {
        foreach ($voucherHeadIds as $voucherHead) {
            // Loop through all bill allocations
            foreach ($billAllocations as $ledgerName => $bills) {
                if (is_array($bills)) {
                    foreach ($bills as $bill) {
                        try {
                            if (isset($bill['NAME'], $bill['AMOUNT'])) {
                                TallyBillAllocation::updateOrCreate(
                                    [
                                        'voucher_head_id' => $voucherHead['id'],
                                        'name' => $bill['NAME'],
                                    ],
                                    [
                                        'bill_amount' => $bill['AMOUNT'],
                                        'year_end' => $bill['YEAREND'] ?? null,
                                        'bill_type' => $bill['BILLTYPE'] ?? null,
                                    ]
                                );
                                Log::info('Successfully processed bill allocation', ['ledger_name' => $ledgerName, 'bill' => $bill]);
                            } else {
                                Log::error('Missing NAME or AMOUNT in BILLALLOCATIONS.LIST entry: ' . json_encode($bill));
                            }
                        } catch (\Exception $e) {
                            Log::error('Error processing bill allocation: ' . $e->getMessage());
                        }
                    }
                } else {
                    Log::info('Invalid bill allocations format for ledger name: ' . $ledgerName);
                }
            }
        }
    }

    private function processBankAllocationsForVoucher($voucherHeadIds, array $bankAllocations)
    {
        foreach ($voucherHeadIds as $voucherHead) {
            $ledgerName = $voucherHead['ledger_name'];

            // Log the voucher head ID and ledger name
            Log::info("Processing voucher head: " . json_encode($voucherHead));

            // Always process bank allocations for the ledger, even if none are found
            if (isset($bankAllocations[$ledgerName]) && is_array($bankAllocations[$ledgerName])) {
                Log::info("Bank allocations found for ledger: " . $ledgerName);

                foreach ($bankAllocations[$ledgerName] as $bank) {
                    // Log the bank allocation data
                    Log::info("Processing bank allocation: " . json_encode($bank));

                    // Sanitize date values
                    $bankDate = $this->sanitizeDate($bank['DATE'] ?? null);
                    $instrumentDate = $this->sanitizeDate($bank['INSTRUMENTDATE'] ?? null);

                    try {
                        // Insert or update the record
                        $allocation = TallyBankAllocation::updateOrCreate(
                            [
                                'voucher_head_id' => $voucherHead['id'] ?? null
                            ],
                            [
                                'bank_date' => $bankDate,
                                'instrument_date' => $instrumentDate,
                                'instrument_number' => $bank['INSTRUMENTNUMBER'] ?? null,
                                'transaction_type' => $bank['TRANSACTIONTYPE'] ?? null,
                                'bank_name' => $bank['BANKNAME'] ?? null,
                                'amount' => $bank['AMOUNT'] ?? null,
                            ]
                        );

                        // Log successful storage
                        Log::info("Bank allocation stored: " . json_encode($allocation));
                    } catch (\Exception $e) {
                        // Log any errors
                        Log::error("Failed to process bank allocation for ledger: " . $ledgerName . " with error: " . $e->getMessage());
                    }
                }
            } else {
                // Even if no bank allocations are found, log this fact
                Log::info("No bank allocations found for ledger: " . $ledgerName);

                // Optionally, you might want to insert an entry with null or default values
                try {
                    $allocation = TallyBankAllocation::updateOrCreate(
                        [
                            'voucher_head_id' => $voucherHead['id'] ?? null
                        ],
                        [
                            'bank_date' => null,
                            'instrument_date' => null,
                            'instrument_number' => null,
                            'transaction_type' => null,
                            'bank_name' => null,
                            'amount' => null,
                        ]
                    );

                    // Log successful storage of default record
                    Log::info("Default bank allocation stored for ledger: " . $ledgerName . " with ID: " . $allocation->id);
                } catch (\Exception $e) {
                    // Log any errors
                    Log::error("Failed to store default bank allocation for ledger: " . $ledgerName . " with error: " . $e->getMessage());
                }
            }
        }
    }

    private function processBatchAllocationsForVoucher(array $inventoryEntriesWithId, array $batchAllocations, $companyGuid)
    {

        Log::info('BatchAllocations company guid: ', ['company_guid' => $companyGuid]);

        foreach ($inventoryEntriesWithId as $inventoryEntries) {
            $stockItemName = $inventoryEntries['stock_item_name'];
            if (isset($batchAllocations[$stockItemName]) && is_array($batchAllocations[$stockItemName])) {
                foreach ($batchAllocations[$stockItemName] as $batch) {
                    if (isset($batch['BATCHNAME'], $batch['AMOUNT'])) {

                        $godownGuid = TallyGodown::where('name', $batch['GODOWNNAME'] ?? null)
                                    ->whereRaw("LEFT(guid, 36) = ?", [$companyGuid])  // Comparing only the first 36 characters of guid
                                    ->value('guid');

                        Log::info('BatchAllocations Godown guid: ', ['godown_guid' => $godownGuid]);

                        TallyBatchAllocation::updateOrCreate(
                            [
                            'voucher_item_id' => $inventoryEntries['id'],
                            'batch_name' => $batch['BATCHNAME'],
                            'godown_name' => $batch['GODOWNNAME'] ?? null,
                            'destination_godown_name' => $batch['DESTINATIONGODOWNNAME'] ?? null,
                            'amount' => $batch['AMOUNT'],
                            'actual_qty' => isset($batch['ACTUALQTY']) ? preg_replace('/[^0-9.]/', '', $batch['ACTUALQTY']) : null,
                            'billed_qty' => isset($batch['BILLEDQTY']) ? preg_replace('/[^0-9.]/', '', $batch['BILLEDQTY']) : null,                           
                            'order_no' => $batch['ORDERNO'] ?? null,
                            'godown_guid' => $godownGuid,
                            ]
                        );
                    }
                }
            }
        }
    }

    private function sanitizeDate($date)
    {
        // Check if date is valid, if not return null or a default value
        if (empty($date) || !strtotime($date)) {
            return null; // or you can return a default value like '0000-00-00'
        }

        // Format date to 'Y-m-d' or other desired format
        return date('Y-m-d', strtotime($date));
    }

    private function convertToDesiredDateFormat($date)
    {
        // Check if the date is in the format 'YYYYMMDD'
        if (preg_match('/^\d{8}$/', $date)) {
            $dateObject = \DateTime::createFromFormat('Ymd', $date);
            return $dateObject ? $dateObject->format('d-M-y') : $date; // Example: '25-Aug-24'
        }
        return $date; // Return original if format is incorrect
    }

    public function reportJsonImport(Request $request)
    {
        try {
            
            $licenseNumber = $request->input('license_number');
    
            if (empty($licenseNumber)) { 
                Log::info('Please enter your license number');
                return response()->json(['error' => 'Please enter your license number'], 400);
            }
    
            $license = TallyLicense::where('license_number', $licenseNumber)->first();
    
            if (!$license) {
                Log::info('License not found for license number: ' . $licenseNumber);
                return response()->json(['error' => 'License not found'], 404);
            } elseif ($license->status != 'Active') {
                Log::info('License not active for license number: ' . $licenseNumber);
                return response()->json(['error' => 'License not active'], 403);
            }
            
            $jsonData = null;

            Log::info('Starting reportJsonImport method.');

            // Check if the request contains 'uploadFile' key for uploaded file
            if ($request->hasFile('uploadFile')) {
                Log::info('File Found in request.', ['request' => $request->all()]);

                $uploadedFile = $request->file('uploadFile');
                $fileName = 'report_data_' . date('YmdHis') . '.json';
                $jsonFilePath = storage_path('app/' . $fileName);

                // Move uploaded file to storage and read its contents
                $uploadedFile->move(storage_path('app'), $fileName);
                $jsonData = file_get_contents($jsonFilePath);

                Log::info('Uploaded file moved to storage and read.', ['file_path' => $jsonFilePath]);

            } else {
                // Get JSON data from request body if no file is uploaded
                $jsonData = $request->getContent();
                Log::info('Using JSON data from request body.', ['jsonData' => $jsonData]);
            }

            // Decode the JSON data
            $data = json_decode($jsonData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error: ' . json_last_error_msg());
            }

            Log::info('Decoded JSON data successfully.', ['data' => $data]);

            // Check if the 'data' key is present or missing and adjust accordingly
            if (isset($data['data'])) {
                // If 'data' key is present, process the nested structure
                $exportDataResponse = $data['data']['BODY']['EXPORTDATARESPONSE'];
            } elseif (isset($data['BODY'])) {
                // If 'data' key is missing, start from 'BODY'
                $exportDataResponse = $data['BODY']['EXPORTDATARESPONSE'];
            } else {
                throw new \Exception('Invalid JSON structure: Missing BODY or data key.');
            }

            // Ensure JSON structure is valid
            if (!isset($exportDataResponse['RESULTDESC']['ROWDESC']['COL']) ||
                !isset($exportDataResponse['RESULTDATA']['ROW'])) {
                throw new \Exception('Invalid JSON structure.');
            }

            // Extract the column definitions (i.e., the mapping of columns)
            $columnDefinitions = $exportDataResponse['RESULTDESC']['ROWDESC']['COL'];

            // Create an associative array to map column names to the respective field in TallyLedger
            $columnMap = [];
            foreach ($columnDefinitions as $index => $column) {
                Log::info('Mapping column.', ['column' => $column]);

                if ($column['NAME'] === '$Guid') {
                    $columnMap[$index] = 'guid';  // Corrected to match the JSON field name
                } elseif ($column['NAME'] === '$Name') {
                    $columnMap[$index] = 'name';
                } elseif ($column['NAME'] === '$_Performance') {
                    $columnMap[$index] = 'performance';
                } elseif ($column['NAME'] === '$_ThisYearBalance') {
                    $columnMap[$index] = 'this_year_balance';
                } elseif ($column['NAME'] === '$_PrevYearBalance') {
                    $columnMap[$index] = 'prev_year_balance';
                } elseif ($column['NAME'] === '$_OnAccountValue') {
                    $columnMap[$index] = 'on_account_value';
                } elseif ($column['NAME'] === '$_CashInFlow') {
                    $columnMap[$index] = 'cash_in_flow';
                } elseif ($column['NAME'] === '$_CashOutFlow') {
                    $columnMap[$index] = 'cash_out_flow';
                } elseif ($column['NAME'] === '$_ThisQuarterBalance') {
                    $columnMap[$index] = 'this_quarter_balance';
                } elseif ($column['NAME'] === '$_PrevQuarterBalance') {
                    $columnMap[$index] = 'prev_quarter_balance';
                }
            }

            Log::info('Completed column mapping.', ['columnMap' => $columnMap]);

            // Now process the rows in the RESULTDATA
            $rows = $exportDataResponse['RESULTDATA']['ROW'];
            Log::info('Processing rows.', ['row_count' => count($rows)]);

            foreach ($rows as $row) {
                $cols = $row['COL'];
                $ledgerData = [];

                foreach ($cols as $index => $value) {
                    if (isset($columnMap[$index])) {
                        $ledgerData[$columnMap[$index]] = $value;
                    }
                }

                Log::info('Processed row data.', ['ledgerData' => $ledgerData]);

                // Check if GUID exists to find the ledger
                if (isset($ledgerData['guid'])) {
                    $ledgerGuid = $ledgerData['guid'];
                    Log::info('Looking for ledger by GUID.', ['guid' => $ledgerGuid]);

                    // Find the ledger by GUID
                    $tallyLedger = TallyLedger::where('guid', $ledgerGuid)->first();

                    if ($tallyLedger) {
                        // Update only the fields that are provided in the request
                        if (isset($ledgerData['this_year_balance'])) {
                            $tallyLedger->this_year_balance = $ledgerData['this_year_balance'];
                        }
                        if (isset($ledgerData['prev_year_balance'])) {
                            $tallyLedger->prev_year_balance = $ledgerData['prev_year_balance'];
                        }
                        if (isset($ledgerData['on_account_value'])) {
                            $tallyLedger->on_account_value = $ledgerData['on_account_value'];
                        }
                        if (isset($ledgerData['cash_in_flow'])) {
                            $tallyLedger->cash_in_flow = $ledgerData['cash_in_flow'];
                        }
                        if (isset($ledgerData['cash_out_flow'])) {
                            $tallyLedger->cash_out_flow = $ledgerData['cash_out_flow'];
                        }
                        if (isset($ledgerData['performance'])) {
                            $tallyLedger->performance = $ledgerData['performance'];
                        }
                        if (isset($ledgerData['this_quarter_balance'])) {
                            $tallyLedger->this_quarter_balance = $ledgerData['this_quarter_balance'];
                        }
                        if (isset($ledgerData['prev_quarter_balance'])) {
                            $tallyLedger->prev_quarter_balance = $ledgerData['prev_quarter_balance'];
                        }

                        // Save the updated ledger record
                        $tallyLedger->save();

                        Log::info('Updated ledger balances', [
                            'guid' => $ledgerGuid,
                            'ledgerData' => $ledgerData
                        ]);
                    } else {
                        Log::warning('Ledger not found for GUID: ' . $ledgerGuid);
                    }
                } else {
                    Log::warning('GUID not found in ledgerData: ' . json_encode($ledgerData));
                }
            }

            return response()->json(['message' => 'Ledger balances updated successfully.']);

        } catch (\Exception $e) {
            Log::error('Error updating ledger balances.', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
