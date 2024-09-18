<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\TallyCompany;
use App\Models\TallyGroup;
use App\Models\TallyLedger;
use App\Models\TallyItem;
use App\Models\TallyUnit;
use App\Models\TallyGodown;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherItem;
use App\Models\TallyBillAllocation;
use App\Models\TallyBatchAllocation;
use App\Models\TallyBankAllocation;
use App\Models\TallyVoucherAccAllocationHead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 

class LedgerController extends Controller
{
    private function findTallyMessage($jsonArray, $path = '') {
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
    
    public function companyJsonImport(Request $request)
    {
        try {
            // Fetch and store the incoming JSON data
            $jsonData = $request->getContent();
            $fileName = 'tally_company_data_' . date('YmdHis') . '.json';
            $jsonFilePath = storage_path('app/' . $fileName);
            file_put_contents($jsonFilePath, $jsonData);
            $jsonData = file_get_contents($jsonFilePath);
            $data = json_decode($jsonData, true);
    
            // Find TALLYMESSAGE key in the JSON data
            $result = $this->findTallyMessage($data);
    
            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }
    
            $messagesPath = $result['path'];
            $messages = $result['value'];
    
            // Process TALLYMESSAGE array
            // Track company GUIDs to ensure they are inserted only once
            $companyGuids = TallyCompany::pluck('guid')->toArray();
            Log::info('Company GUIDs in Database:', ['companyGuids' => $companyGuids]);
    
            foreach ($messages as $message) {
                // Handle company data
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
            $jsonData = $request->getContent();
            $fileName = 'tally_master_data_' . date('YmdHis') . '.json';
            $jsonFilePath = storage_path('app/' . $fileName);
            file_put_contents($jsonFilePath, $jsonData);
            $jsonData = file_get_contents($jsonFilePath);
            $data = json_decode($jsonData, true);

            // Determine the structure of the JSON and extract messages accordingly
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

                    $guid = $groupData['GUID'] ?? null;
                    $companyGuid = substr($guid, 0, 36);

                    // Convert array fields to strings
                    $nameField = $groupData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? null;
                    if (is_array($nameField)) {
                        $nameField = implode(', ', $nameField);
                    }

                    $tallyGroup = TallyGroup::updateOrCreate(
                        ['guid' => $guid],
                        [
                            'company_guid' => $companyGuid,
                            'parent' => $groupData['PARENT'] ?? null,
                            'affects_stock' => $groupData['AFFECTSSTOCK'] ?? null,
                            'alter_id' => $groupData['ALTERID'] ?? null,
                            'name' => $nameField,
                        ]
                    );

                    if (!$tallyGroup) {
                        throw new \Exception('Failed to create or update tally group record.');
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

                    $nameField = $ledgerData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? [];
                    if (is_array($nameField)) {
                        $languageName = $nameField[0] ?? null;
                        $alias = $nameField[1] ?? null;
                    } else {
                        $languageName = $nameField;
                        $alias = null;
                    }

                    $tallyLedger = TallyLedger::updateOrCreate(
                        ['guid' => $guid],
                        [
                            'company_guid' => $companyGuid,
                            'parent' => $ledgerData['PARENT'] ?? null,
                            'tax_classification_name' => html_entity_decode($ledgerData['TAXCLASSIFICATIONNAME'] ?? null),
                            'tax_type' => $ledgerData['TAXTYPE'] ?? null,
                            'bill_credit_period' => $ledgerData['BILLCREDITPERIOD'] ?? null,
                            'credit_limit' => $ledgerData['CREDITLIMIT'] ?? null,
                            'gst_type' => html_entity_decode($ledgerData['GSTTYPE'] ?? null),
                            'appropriate_for' => html_entity_decode($ledgerData['APPROPRIATEFOR'] ?? null),
                            'party_gst_in' => $ledgerData['PARTYGSTIN'] ?? null,
                            'gst_duty_head' => $ledgerData['GSTDUTYHEAD'] ?? null,
                            'service_category' => html_entity_decode($ledgerData['SERVICECATEGORY'] ?? null),
                            'gst_registration_type' => $ledgerData['GSTREGISTRATIONTYPE'] ?? null,
                            'excise_ledger_classification' => html_entity_decode($ledgerData['EXCISELEDGERCLASSIFICATION'] ?? null),
                            'excise_duty_type' => html_entity_decode($ledgerData['EXCISEDUTYTYPE'] ?? null),
                            'excise_nature_of_purchase' => html_entity_decode($ledgerData['EXCISENATUREOFPURCHASE'] ?? null),
                            'ledger_fbt_category' => html_entity_decode($ledgerData['LEDGERFBTCATEGORY'] ?? null),
                            'is_bill_wise_on' => $ledgerData['ISBILLWISEON'] ?? null,
                            'is_cost_centres_on' => $ledgerData['ISCOSTCENTRESON'] ?? null,
                            'alter_id' => $ledgerData['ALTERID'] ?? null,
                            'opening_balance' => $ledgerData['OPENINGBALANCE'] ?? null,
                            'language_name' => $languageName,
                            'alias' => $alias,
                            'language_id' => $ledgerData['LANGUAGENAME.LIST']['LANGUAGEID'] ?? null,
                            'applicable_from' => $applicableFrom,
                            'ledger_gst_registration_type' => $ledgerData['LEDGSTREGDETAILS.LIST']['GSTREGISTRATIONTYPE'] ?? null,
                            'gst_in' => $ledgerData['LEDGSTREGDETAILS.LIST']['GSTIN'] ?? null,
                            'email' => $ledgerData['EMAIL'] ?? null,
                            'phone_no' => $ledgerData['LEDGERMOBILE'] ?? null,
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
            $jsonData = $request->getContent();
            $fileName = 'tally_stock_item_data_' . date('YmdHis') . '.json';
            $jsonFilePath = storage_path('app/' . $fileName);
            file_put_contents($jsonFilePath, $jsonData);
            $jsonData = file_get_contents($jsonFilePath);
            $data = json_decode($jsonData, true);
    
            // Find TALLYMESSAGE key in the JSON data
            $result = $this->findTallyMessage($data);
            
            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }
    
            $messagesPath = $result['path'];
            $messages = $result['value'];
    
            // Process each TALLYMESSAGE
            foreach ($messages as $message) {
                if (isset($message['UNIT'])) {
                    $unitData = $message['UNIT'];
                    Log::info('Unit Data:', ['unitData' => $unitData]);
    
                    // Extract REPORTINGUQCDETAILS.LIST
                    $reportingUQCDetails = $unitData['REPORTINGUQCDETAILS.LIST'] ?? [];
                    $reportingUQCName = $reportingUQCDetails['REPORTINGUQCNAME'] ?? null;
                    $applicableFrom = $reportingUQCDetails['APPLICABLEFROM'] ?? null;

                    $name = is_array($unitData['NAME']) ? $unitData['NAME'][0] : $unitData['NAME'];
    
                    $tallyUnit = TallyUnit::updateOrCreate(
                        ['guid' => $unitData['GUID'] ?? null],
                        [
                            'name' => $name,
                            'is_updating_target_id' => $unitData['ISUPDATINGTARGETID'] ?? null,
                            'is_deleted' => $unitData['ISDELETED'] ?? null,
                            'is_security_on_when_entered' => $unitData['ISSECURITYONWHENENTERED'] ?? null,
                            'as_original' => $unitData['ASORIGINAL'] ?? null,
                            'is_gst_excluded' => $unitData['ISGSTEXCLUDED'] ?? null,
                            'is_simple_unit' => $unitData['ISSIMPLEUNIT'] ?? null,
                            'alter_id' => $unitData['ALTERID'] ?? null,
                            'reporting_uqc_name' => $reportingUQCName,
                            'applicable_from' => $applicableFrom,
                        ]
                    );
    
                    if (!$tallyUnit) {
                        throw new \Exception('Failed to create or update tally unit record.');
                    }
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
                            'job_name' => $godownData['JOBNAME'] ?? null,
                            'are1_serial_master' => $godownData['ARE1SERIALMASTER'] ?? null,
                            'are2_serial_master' => $godownData['ARE2SERIALMASTER'] ?? null,
                            'are3_serial_master' => $godownData['ARE3SERIALMASTER'] ?? null,
                            'tax_unit_name' => $godownData['TAXUNITNAME'] ?? null,
                            'is_updating_target_id' => $godownData['ISUPDATINGTARGETID'] ?? null,
                            'is_deleted' => $godownData['ISDELETED'] ?? null,
                            'is_security_on_when_entered' => $godownData['ISSECURITYONWHENENTERED'] ?? null,
                            'as_original' => $godownData['ASORIGINAL'] ?? null,
                            'has_no_space' => $godownData['HASNOSPACE'] ?? null,
                            'has_no_stock' => $godownData['HASNOSTOCK'] ?? null,
                            'is_external' => $godownData['ISEXTERNAL'] ?? null,
                            'is_internal' => $godownData['ISINTERNAL'] ?? null,
                            'enable_export' => $godownData['ENABLEEXPORT'] ?? null,
                            'is_primary_excise_unit' => $godownData['ISPRIMARYEXCISEUNIT'] ?? null,
                            'allow_export_rebate' => $godownData['ALLOWEXPORTREBATE'] ?? null,
                            'is_trader_rg_number_on' => $godownData['ISTRADERRGNUMBERON'] ?? null,
                            'alter_id' => $godownData['ALTERID'] ?? null,
                            'language_name' => $nameField,
                            'language_id' => $godownData['LANGUAGENAME.LIST']['LANGUAGEID'] ?? null,
                        ]
                    );
    
                    if (!$tallyGodown) {
                        throw new \Exception('Failed to create or update tally Godown record.');
                    }
                }
            }
    
            foreach ($messages as $message) {
                if (isset($message['STOCKITEM'])) {
                    $stockItemData = $message['STOCKITEM'];
                    Log::info('Stock Item Data:', ['stockItemData' => $stockItemData]);
    
                    $nameField = $stockItemData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? [];
                    if (is_array($nameField)) {
                        $languageName = $nameField[0] ?? null;
                        $alias = $nameField[1] ?? null;
                    } else {
                        $languageName = $nameField;
                        $alias = null;
                    }

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
    
                    $tallyStockItem = TallyItem::updateOrCreate(
                        ['guid' => $stockItemData['GUID'] ?? null],
                        [
                            'company_guid' => $companyGuid,
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
                            'base_units' => $stockItemData['BASEUNITS'] ?? null,
                            'additional_units' => $stockItemData['ADDITIONALUNITS'] ?? null,
                            'excise_item_classification' => $stockItemData['EXCISEITEMCLASSIFICATION'] ?? null,
                            'vat_base_unit' => $stockItemData['VATBASEUNIT'] ?? null,
                            'is_cost_centres_on' => $stockItemData['ISCOSTCENTRESON'] ?? null,
                            'is_batch_wise_on' => $stockItemData['ISBATCHWISEON'] ?? null,
                            'is_perishable_on' => $stockItemData['ISPERISHABLEON'] ?? null,
                            'is_entry_tax_applicable' => $stockItemData['ISENTRYTAXAPPLICABLE'] ?? null,
                            'is_cost_tracking_on' => $stockItemData['ISCOSTTRACKINGON'] ?? null,
                            'is_updating_target_id' => $stockItemData['ISUPDATINGTARGETID'] ?? null,
                            'is_deleted' => $stockItemData['ISDELETED'] ?? null,
                            'is_security_on_when_entered' => $stockItemData['ISSECURITYONWHENENTERED'] ?? null,
                            'as_original' => $stockItemData['ASORIGINAL'] ?? null,
                            'is_rate_inclusive_vat' => $stockItemData['ISRATEINCLUSIVEVAT'] ?? null,
                            'ignore_physical_difference' => $stockItemData['IGNOREPHYSICALDIFFERENCE'] ?? null,
                            'ignore_negative_stock' => $stockItemData['IGNORENEGATIVESTOCK'] ?? null,
                            'treat_sales_as_manufactured' => $stockItemData['TREATSALESASMANUFACTURED'] ?? null,
                            'treat_purchases_as_consumed' => $stockItemData['TREATPURCHASESASCONSUMED'] ?? null,
                            'treat_rejects_as_scrap' => $stockItemData['TREATREJECTSASSCRAP'] ?? null,
                            'has_mfg_date' => $stockItemData['HASMFGDATE'] ?? null,
                            'allow_use_of_expired_items' => $stockItemData['ALLOWUSEOFEXPIREDITEMS'] ?? null,
                            'ignore_batches' => $stockItemData['IGNOREBATCHES'] ?? null,
                            'ignore_godowns' => $stockItemData['IGNOREGODOWNS'] ?? null,
                            'adj_diff_in_first_sale_ledger' => $stockItemData['ADJDIFFINFIRSTSALELEDGER'] ?? null,
                            'adj_diff_in_first_purc_ledger' => $stockItemData['ADJDIFFINFIRSTPURCLEDGER'] ?? null,
                            'cal_con_mrp' => $stockItemData['CALCONMRP'] ?? null,
                            'exclude_jrnl_for_valuation' => $stockItemData['EXCLUDEJRNLFORVALUATION'] ?? null,
                            'is_mrp_incl_of_tax' => $stockItemData['ISMRPINCLOFTAX'] ?? null,
                            'is_addl_tax_exempt' => $stockItemData['ISADDLTAXEXEMPT'] ?? null,
                            'is_supplementry_duty_on' => $stockItemData['ISSUPPLEMENTRYDUTYON'] ?? null,
                            'gvat_is_excise_appl' => $stockItemData['GVATISEXCISEAPPL'] ?? null,
                            'is_additional_tax' => $stockItemData['ISADDITIONALTAX'] ?? null,
                            'is_cess_exempted' => $stockItemData['ISCESSEXEMPTED'] ?? null,
                            'reorder_as_higher' => $stockItemData['REORDERASHIGHER'] ?? null,
                            'min_order_as_higher' => $stockItemData['MINORDERASHIGHER'] ?? null,
                            'is_excise_calculate_on_mrp' => $stockItemData['ISEXCISECALCULATEONMRP'] ?? null,
                            'inclusive_tax' => $stockItemData['INCLUSIVETAX'] ?? null,
                            'gst_calc_slab_on_mrp' => $stockItemData['GSTCALCSLABONMRP'] ?? null,
                            'modify_mrp_rate' => $stockItemData['MODIFYMRPRATE'] ?? null,
                            'alter_id' => $stockItemData['ALTERID'] ?? null,
                            'denominator' => $stockItemData['DENOMINATOR'] ?? null,
                            'basic_rate_of_excise' => $stockItemData['BASICRATEOFEXCISE'] ?? null,
                            'rate_of_vat' => $stockItemData['RATEOFVAT'] ?? null,
                            'vat_base_no' => $stockItemData['VATBASENO'] ?? null,
                            'vat_trail_no' => $stockItemData['VATTRAILNO'] ?? null,
                            'vat_actual_ratio' => $stockItemData['VATACTUALRATIO'] ?? null,
                            'opening_balance' => $stockItemData['OPENINGBALANCE'] ?? null,
                            'opening_value' => $stockItemData['OPENINGVALUE'] ?? null,
                            'opening_rate' => $stockItemData['OPENINGRATE'] ?? null,
                            'unit' => $unit,
                            'igst_rate' => $igstRate,
                            'hsn_code' => $stockItemData['HSNDETAILS.LIST']['HSNCODE'] ?? null,
                            'gst_details' => json_encode($stockItemData['GSTDETAILS.LIST'] ?? []),
                            'hsn_details' => json_encode($stockItemData['HSNDETAILS.LIST'] ?? []),
                            'language_name' => $languageName,
                            'alias' => $alias,
                            'language_id' => $stockItemData['LANGUAGENAME.LIST']['LANGUAGEID'] ?? null,
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

    // public function voucherJsonImport(Request $request)
    // {
    //     try {
    //         $jsonData = $request->getContent();
    //         $fileName = 'tally_voucher_data_' . now()->format('YmdHis') . '.json';
    //         $jsonFilePath = storage_path('app/' . $fileName);
    //         file_put_contents($jsonFilePath, $jsonData);
    //         $data = json_decode(file_get_contents($jsonFilePath), true);

    //         // Find TALLYMESSAGE key in the JSON data
    //         $result = $this->findTallyMessage($data);

    //         if ($result === null) {
    //             throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
    //         }

    //         $messagesPath = $result['path'];
    //         $messages = $result['value'];

    //         // Store ledger name to head_id mapping
    //         $ledgerHeadMap = [];

    //         foreach ($messages as $message) {
    //             if (isset($message['VOUCHER'])) {
    //                 $voucherData = $message['VOUCHER'];
    //                 $partyLedgerName = $voucherData['PARTYLEDGERNAME'] ?? $voucherData['PARTYNAME'] ?? null;

    //                 $ledgerEntries = $this->normalizeEntries($voucherData['LEDGERENTRIES.LIST'] ?? []);
    //                 $allLedgerEntries = $this->normalizeEntries($voucherData['ALLLEDGERENTRIES.LIST'] ?? []);
    //                 $inventoryEntries = $this->normalizeEntries($voucherData['ALLINVENTORYENTRIES.LIST'] ?? []);

    //                 $accountingAllocations = [];
    //                 foreach ($inventoryEntries as $inventoryEntry) {
    //                     if (isset($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST'])) {
    //                         $accountingAllocations = array_merge($accountingAllocations, $this->normalizeEntries($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST']));
    //                     }
    //                 }
                    
    //                 $combinedLedgerEntries = array_merge($ledgerEntries, $allLedgerEntries);


    //                 $inventoryEntries = $this->normalizeEntries($voucherData['ALLINVENTORYENTRIES.LIST'] ?? []);
    //                 $billAllocations = [];
    //                 foreach ($combinedLedgerEntries as $ledgerEntry) {
    //                     if (isset($ledgerEntry['BILLALLOCATIONS.LIST'])) {
    //                         $billAllocations[$ledgerEntry['LEDGERNAME']] = $this->normalizeEntries($ledgerEntry['BILLALLOCATIONS.LIST']);
    //                     }
    //                 }

    //                 $bankAllocations = [];
    //                 foreach ($combinedLedgerEntries as $ledgerEntry) {
    //                     if (isset($ledgerEntry['BANKALLOCATIONS.LIST'])) {
    //                         $bankAllocations[$ledgerEntry['LEDGERNAME']] = $this->normalizeEntries($ledgerEntry['BANKALLOCATIONS.LIST']);
    //                     }
    //                 }

    //                 $batchAllocations = [];
    //                 foreach ($inventoryEntries as $inventoryEntry) {
    //                     if (isset($inventoryEntry['BATCHALLOCATIONS.LIST'])) {
    //                         $batchAllocations[$inventoryEntry['STOCKITEMNAME']] = $this->normalizeEntries($inventoryEntry['BATCHALLOCATIONS.LIST']);
    //                     }
    //                 }

    //                 $combinedLedgerEntries = $this->processLedgerEntries($combinedLedgerEntries);
    //                 $inventoryEntries = $this->processInventoryEntries($inventoryEntries);
    //                 $accountingAllocations = $this->processAccountingAllocations($accountingAllocations);

    //                 $consigneeAddressList = $voucherData['BASICBUYERADDRESS.LIST']['BASICBUYERADDRESS'] ?? null;
    //                 if (is_array($consigneeAddressList)) {
    //                     $consigneeAddressList = implode(', ', $consigneeAddressList);
    //                 }

    //                 $buyerAddressList = $voucherData['ADDRESS.LIST']['ADDRESS'] ?? null;
    //                 if (is_array($buyerAddressList)) {
    //                     $buyerAddressList = implode(', ', $buyerAddressList);
    //                 }

    //                 $invoiceDelNotes = is_array($voucherData['INVOICEDELNOTES.LIST'] ?? null) ? $voucherData['INVOICEDELNOTES.LIST'] : [];
    //                 $deliveryNotes = [];
    //                 $formattedShippingDates = []; // Separate array to store formatted dates

    //                 foreach ($invoiceDelNotes as $note) {
    //                     if (isset($note['BASICSHIPDELIVERYNOTE']) && isset($note['BASICSHIPPINGDATE'])) {
    //                         // Convert BASICSHIPPINGDATE to the desired format (e.g., '25-Aug-24')
    //                         $formattedDate = $this->convertToDesiredDateFormat($note['BASICSHIPPINGDATE']);
    //                         $formattedShippingDates[] = $formattedDate; // Save each formatted date separately

    //                         $deliveryNotes[] = $note['BASICSHIPDELIVERYNOTE'];
    //                     }
    //                 }
    //                 $deliveryNotesStr = implode(', ', $deliveryNotes);

    //                 $ledgerGuid = TallyLedger::where('language_name', $partyLedgerName)->value('guid');
    //                 $tallyVoucher = TallyVoucher::updateOrCreate(
    //                     ['guid' => $voucherData['GUID']],
    //                     [
    //                         'company_guid' => substr($voucherData['GUID'], 0, 36),
    //                         'voucher_type' => $voucherData['VOUCHERTYPENAME'] ?? null,
    //                         'is_cancelled' => $voucherData['ISCANCELLED'] ?? null,
    //                         'alter_id' => $voucherData['ALTERID'] ?? null,
    //                         'party_ledger_name' => $partyLedgerName,
    //                         'ledger_guid' => $ledgerGuid,
    //                         'voucher_number' => $voucherData['VOUCHERNUMBER'] ?? null,
    //                         'voucher_date' => $voucherData['DATE'] ?? null,
    //                         'reference_date' => !empty($voucherData['REFERENCEDATE']) ? $voucherData['REFERENCEDATE'] : null, 
    //                         'reference_no' => $voucherData['REFERENCE'] ?? null,
    //                         'place_of_supply' => $voucherData['PLACEOFSUPPLY'] ?? null,
    //                         'country_of_residense' => $voucherData['COUNTRYOFRESIDENCE'] ?? null,
    //                         'gst_registration_type' => $voucherData['GSTREGISTRATIONTYPE'] ?? null,
    //                         'numbering_style' => $voucherData['NUMBERINGSTYLE'] ?? null,
    //                         'narration' => $voucherData['NARRATION'] ?? null,
    //                         'order_no' => $voucherData['INVOICEORDERLIST.LIST']['BASICPURCHASEORDERNO'] ?? null,
    //                         'order_date' => $voucherData['INVOICEORDERLIST.LIST']['BASICORDERDATE'] ?? null,
    //                         'ship_doc_no' => $voucherData['BASICSHIPDOCUMENTNO'] ?? null,
    //                         'ship_by' => $voucherData['BASICSHIPPEDBY'] ?? null,
    //                         'final_destination' => $voucherData['BASICFINALDESTINATION'] ?? null,
    //                         'bill_lading_no' => $voucherData['BILLOFLADINGNO'] ?? null,
    //                         'bill_lading_date' => !empty($voucherData['BILLOFLADINGDATE']) ? $voucherData['BILLOFLADINGDATE'] : null, 
    //                         'vehicle_no' => $voucherData['BASICSHIPVESSELNO'] ?? null,
    //                         'terms' => $voucherData['BASICORDERTERMS.LIST']['BASICORDERTERMS'] ?? null,
    //                         'consignee_name' => $voucherData['BASICBUYERNAME'] ?? null,
    //                         'consignee_state_name' => $voucherData['CONSIGNEESTATENAME'] ?? null,
    //                         'consignee_gstin' => $voucherData['CONSIGNEEGSTIN'] ?? null,
    //                         'consignee_addr' => $consigneeAddressList,
    //                         'buyer_addr' => $buyerAddressList,
    //                         'delivery_notes' => $deliveryNotesStr, 
    //                         'delivery_dates' => json_encode($formattedShippingDates),
    //                         'due_date_payment' => $voucherData['BASICDUEDATEOFPYMT'] ?? null,
    //                         'buyer_gstin' => $voucherData['PARTYGSTIN'] ?? null,
    //                         'order_ref' => $voucherData['BASICORDERREF'] ?? null,
    //                         'cost_center_name' => $voucherData['COSTCENTRENAME'] ?? null,
    //                         'cost_center_amount' => $voucherData['COSTCENTREAMOUNT'] ?? null,
    //                     ]
    //                 );

    //                 if ($tallyVoucher) {
    //                     $voucherHeadIds = $this->processLedgerEntriesForVoucher($tallyVoucher->id, $combinedLedgerEntries);
    //                     $voucherHeadIds = $this->processAccountingAllocationForVoucher($tallyVoucher->id, $accountingAllocations);
    //                     $inventoryEntriesWithId = $this->processInventoryEntriesForVoucher($tallyVoucher->id, $inventoryEntries);
    //                     $this->processBillAllocationsForVoucher($voucherHeadIds, $billAllocations);
    //                     $this->processBankAllocationsForVoucher($voucherHeadIds, $bankAllocations);
    //                     $this->processBatchAllocationsForVoucher($inventoryEntriesWithId, $batchAllocations);
    //                 } else {
    //                     throw new \Exception('Failed to create or update voucher item record.');
    //                 }
    //             }
    //         }

    //         return response()->json(['message' => 'Tally data saved successfully.', 'path' => $messagesPath]);
    //     } catch (\Exception $e) {
    //         Log::error('Error saving Tally voucher data: ' . $e->getMessage());
    //         return response()->json(['error' => 'Failed to save Tally voucher data.'], 500);
    //     }
    // }



    public function voucherJsonImport(Request $request)
    {
        try {
            $jsonData = $request->getContent();
            $fileName = 'tally_voucher_data_' . now()->format('YmdHis') . '.json';
            $jsonFilePath = storage_path('app/' . $fileName);
            file_put_contents($jsonFilePath, $jsonData);
            $data = json_decode(file_get_contents($jsonFilePath), true);

            // Find TALLYMESSAGE key in the JSON data
            $result = $this->findTallyMessage($data);

            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }

            $messagesPath = $result['path'];
            $messages = $result['value'];

            // Store ledger name to head_id mapping
            $ledgerHeadMap = [];

            foreach ($messages as $message) {
                if (isset($message['VOUCHER'])) {
                    $voucherData = $message['VOUCHER'];
                    $partyLedgerName = $voucherData['PARTYLEDGERNAME'] ?? $voucherData['PARTYNAME'] ?? null;
                    $companyGuid = substr($voucherData['GUID'], 0, 36); 

                    $ledgerEntries = $this->normalizeEntries($voucherData['LEDGERENTRIES.LIST'] ?? []);
                    $allLedgerEntries = $this->normalizeEntries($voucherData['ALLLEDGERENTRIES.LIST'] ?? []);
                    $inventoryEntries = $this->normalizeEntries($voucherData['ALLINVENTORYENTRIES.LIST'] ?? []);

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

                    $combinedLedgerEntries = $this->processLedgerEntries($combinedLedgerEntries);
                    $inventoryEntries = $this->processInventoryEntries($inventoryEntries);
                    $accountingAllocations = $this->processAccountingAllocations($accountingAllocations);

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

                    $ledgerGuid = TallyLedger::where('language_name', $partyLedgerName)
                                                ->where('company_guid', $companyGuid)
                                                ->value('guid');
                    
                    // Find existing Tally Voucher
                    $existingVoucher = TallyVoucher::where('guid', $voucherData['GUID'])
                                    ->where('company_guid', $companyGuid)
                                    ->first();

                    // if ($existingVoucher) {
                    //     // Delete all related data
                    //     $this->deleteRelatedVoucherData($existingVoucher->id);

                    //     // Delete existing voucher
                    //     $existingVoucher->delete();
                    // }



                    if ($existingVoucher) {
                        // Retrieve the related voucher head IDs
                        $voucherHeadIds = TallyVoucherHead::where('tally_voucher_id', $existingVoucher->id)->pluck('id')->toArray();
                        $inventoryEntriesWithId = TallyVoucherItem::where('id', $existingVoucher->id)->pluck('id')->toArray();

                        // Delete all related data
                        $this->deleteRelatedVoucherData($existingVoucher->id, $voucherHeadIds, $inventoryEntriesWithId);

                        // Delete existing voucher
                        $existingVoucher->delete();
                    }


                    // Create new Tally Voucher
                    $tallyVoucher = TallyVoucher::create([
                        'guid' => $voucherData['GUID'],
                        'company_guid' => substr($voucherData['GUID'], 0, 36),
                        'voucher_type' => $voucherData['VOUCHERTYPENAME'] ?? null,
                        'is_cancelled' => $voucherData['ISCANCELLED'] ?? null,
                        'alter_id' => $voucherData['ALTERID'] ?? null,
                        'party_ledger_name' => $partyLedgerName,
                        'ledger_guid' => $ledgerGuid,
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
                        'terms' => $voucherData['BASICORDERTERMS.LIST']['BASICORDERTERMS'] ?? null,
                        'consignee_name' => $voucherData['BASICBUYERNAME'] ?? null,
                        'consignee_state_name' => $voucherData['CONSIGNEESTATENAME'] ?? null,
                        'consignee_gstin' => $voucherData['CONSIGNEEGSTIN'] ?? null,
                        'consignee_addr' => $consigneeAddressList,
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
                        $voucherHeadIds = $this->processLedgerEntriesForVoucher($tallyVoucher->id, $combinedLedgerEntries);
                        $voucherHeadIds = $this->processAccountingAllocationForVoucher($tallyVoucher->id, $accountingAllocations);
                        $inventoryEntriesWithId = $this->processInventoryEntriesForVoucher($tallyVoucher->id, $inventoryEntries);
                        $this->processBillAllocationsForVoucher($voucherHeadIds, $billAllocations);
                        $this->processBankAllocationsForVoucher($voucherHeadIds, $bankAllocations);
                        $this->processBatchAllocationsForVoucher($inventoryEntriesWithId, $batchAllocations);
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



    // public function voucherJsonImport(Request $request)
    // {
    //     try {
    //         $jsonData = $request->getContent();
    //         $fileName = 'tally_voucher_data_' . now()->format('YmdHis') . '.json';
    //         $jsonFilePath = storage_path('app/' . $fileName);
    //         file_put_contents($jsonFilePath, $jsonData);
    //         $data = json_decode(file_get_contents($jsonFilePath), true);

    //         // Find TALLYMESSAGE key in the JSON data
    //         $result = $this->findTallyMessage($data);

    //         if ($result === null) {
    //             throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
    //         }

    //         $messagesPath = $result['path'];
    //         $messages = $result['value'];

    //         // Store ledger name to head_id mapping
    //         $ledgerHeadMap = [];

    //         foreach ($messages as $message) {
    //             if (isset($message['VOUCHER'])) {
    //                 $voucherData = $message['VOUCHER'];
    //                 $partyLedgerName = $voucherData['PARTYLEDGERNAME'] ?? $voucherData['PARTYNAME'] ?? null;

    //                 $ledgerEntries = $this->normalizeEntries($voucherData['LEDGERENTRIES.LIST'] ?? []);
    //                 $allLedgerEntries = $this->normalizeEntries($voucherData['ALLLEDGERENTRIES.LIST'] ?? []);
    //                 $inventoryEntries = $this->normalizeEntries($voucherData['ALLINVENTORYENTRIES.LIST'] ?? []);

    //                 $accountingAllocations = [];
    //                 foreach ($inventoryEntries as $inventoryEntry) {
    //                     if (isset($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST'])) {
    //                         $accountingAllocations = array_merge($accountingAllocations, $this->normalizeEntries($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST']));
    //                     }
    //                 }

    //                 $combinedLedgerEntries = array_merge($ledgerEntries, $allLedgerEntries);

    //                 $inventoryEntries = $this->normalizeEntries($voucherData['ALLINVENTORYENTRIES.LIST'] ?? []);
    //                 $billAllocations = [];
    //                 foreach ($combinedLedgerEntries as $ledgerEntry) {
    //                     if (isset($ledgerEntry['BILLALLOCATIONS.LIST'])) {
    //                         $billAllocations[$ledgerEntry['LEDGERNAME']] = $this->normalizeEntries($ledgerEntry['BILLALLOCATIONS.LIST']);
    //                     }
    //                 }

    //                 $bankAllocations = [];
    //                 foreach ($combinedLedgerEntries as $ledgerEntry) {
    //                     if (isset($ledgerEntry['BANKALLOCATIONS.LIST'])) {
    //                         $bankAllocations[$ledgerEntry['LEDGERNAME']] = $this->normalizeEntries($ledgerEntry['BANKALLOCATIONS.LIST']);
    //                     }
    //                 }

    //                 $batchAllocations = [];
    //                 foreach ($inventoryEntries as $inventoryEntry) {
    //                     if (isset($inventoryEntry['BATCHALLOCATIONS.LIST'])) {
    //                         $batchAllocations[$inventoryEntry['STOCKITEMNAME']] = $this->normalizeEntries($inventoryEntry['BATCHALLOCATIONS.LIST']);
    //                     }
    //                 }

    //                 $combinedLedgerEntries = $this->processLedgerEntries($combinedLedgerEntries);
    //                 $inventoryEntries = $this->processInventoryEntries($inventoryEntries);
    //                 $accountingAllocations = $this->processAccountingAllocations($accountingAllocations);

    //                 $consigneeAddressList = $voucherData['BASICBUYERADDRESS.LIST']['BASICBUYERADDRESS'] ?? null;
    //                 if (is_array($consigneeAddressList)) {
    //                     $consigneeAddressList = implode(', ', $consigneeAddressList);
    //                 }

    //                 $buyerAddressList = $voucherData['ADDRESS.LIST']['ADDRESS'] ?? null;
    //                 if (is_array($buyerAddressList)) {
    //                     $buyerAddressList = implode(', ', $buyerAddressList);
    //                 }

    //                 $invoiceDelNotes = is_array($voucherData['INVOICEDELNOTES.LIST'] ?? null) ? $voucherData['INVOICEDELNOTES.LIST'] : [];
    //                 $deliveryNotes = [];
    //                 $formattedShippingDates = [];

    //                 foreach ($invoiceDelNotes as $note) {
    //                     if (isset($note['BASICSHIPDELIVERYNOTE']) && isset($note['BASICSHIPPINGDATE'])) {
    //                         $formattedDate = $this->convertToDesiredDateFormat($note['BASICSHIPPINGDATE']);
    //                         $formattedShippingDates[] = $formattedDate;
    //                         $deliveryNotes[] = $note['BASICSHIPDELIVERYNOTE'];
    //                     }
    //                 }
    //                 $deliveryNotesStr = implode(', ', $deliveryNotes);

    //                 $ledgerGuid = TallyLedger::where('language_name', $partyLedgerName)->value('guid');
                    
    //                 // Find existing Tally Voucher
    //                 $existingVoucher = TallyVoucher::where('guid', $voucherData['GUID'])->first();

    //                 // if ($existingVoucher) {
    //                 //     // Delete all related data
    //                 //     $this->deleteRelatedVoucherData($existingVoucher->id);

    //                 //     // Delete existing voucher
    //                 //     $existingVoucher->delete();
    //                 // }



    //                 if ($existingVoucher) {
    //                     // Retrieve the related voucher head IDs
    //                     $voucherHeadIds = TallyVoucherHead::where('tally_voucher_id', $existingVoucher->id)->pluck('id')->toArray();
    //                     $inventoryEntriesWithId = TallyVoucherItem::where('id', $existingVoucher->id)->pluck('id')->toArray();

    //                     // Delete all related data
    //                     $this->deleteRelatedVoucherData($existingVoucher->id, $voucherHeadIds, $inventoryEntriesWithId);

    //                     // Delete existing voucher
    //                     $existingVoucher->delete();
    //                 }


    //                 // Create new Tally Voucher
    //                 $tallyVoucher = TallyVoucher::create([
    //                     'guid' => $voucherData['GUID'],
    //                     'company_guid' => substr($voucherData['GUID'], 0, 36),
    //                     'voucher_type' => $voucherData['VOUCHERTYPENAME'] ?? null,
    //                     'is_cancelled' => $voucherData['ISCANCELLED'] ?? null,
    //                     'alter_id' => $voucherData['ALTERID'] ?? null,
    //                     'party_ledger_name' => $partyLedgerName,
    //                     'ledger_guid' => $ledgerGuid,
    //                     'voucher_number' => $voucherData['VOUCHERNUMBER'] ?? null,
    //                     'voucher_date' => $voucherData['DATE'] ?? null,
    //                     'reference_date' => !empty($voucherData['REFERENCEDATE']) ? $voucherData['REFERENCEDATE'] : null, 
    //                     'reference_no' => $voucherData['REFERENCE'] ?? null,
    //                     'place_of_supply' => $voucherData['PLACEOFSUPPLY'] ?? null,
    //                     'country_of_residense' => $voucherData['COUNTRYOFRESIDENCE'] ?? null,
    //                     'gst_registration_type' => $voucherData['GSTREGISTRATIONTYPE'] ?? null,
    //                     'numbering_style' => $voucherData['NUMBERINGSTYLE'] ?? null,
    //                     'narration' => $voucherData['NARRATION'] ?? null,
    //                     'order_no' => $voucherData['INVOICEORDERLIST.LIST']['BASICPURCHASEORDERNO'] ?? null,
    //                     'order_date' => $voucherData['INVOICEORDERLIST.LIST']['BASICORDERDATE'] ?? null,
    //                     'ship_doc_no' => $voucherData['BASICSHIPDOCUMENTNO'] ?? null,
    //                     'ship_by' => $voucherData['BASICSHIPPEDBY'] ?? null,
    //                     'final_destination' => $voucherData['BASICFINALDESTINATION'] ?? null,
    //                     'bill_lading_no' => $voucherData['BILLOFLADINGNO'] ?? null,
    //                     'bill_lading_date' => !empty($voucherData['BILLOFLADINGDATE']) ? $voucherData['BILLOFLADINGDATE'] : null, 
    //                     'vehicle_no' => $voucherData['BASICSHIPVESSELNO'] ?? null,
    //                     'terms' => $voucherData['BASICORDERTERMS.LIST']['BASICORDERTERMS'] ?? null,
    //                     'consignee_name' => $voucherData['BASICBUYERNAME'] ?? null,
    //                     'consignee_state_name' => $voucherData['CONSIGNEESTATENAME'] ?? null,
    //                     'consignee_gstin' => $voucherData['CONSIGNEEGSTIN'] ?? null,
    //                     'consignee_addr' => $consigneeAddressList,
    //                     'buyer_addr' => $buyerAddressList,
    //                     'delivery_notes' => $deliveryNotesStr, 
    //                     'delivery_dates' => json_encode($formattedShippingDates),
    //                     'due_date_payment' => $voucherData['BASICDUEDATEOFPYMT'] ?? null,
    //                     'buyer_gstin' => $voucherData['PARTYGSTIN'] ?? null,
    //                     'order_ref' => $voucherData['BASICORDERREF'] ?? null,
    //                     'cost_center_name' => $voucherData['COSTCENTRENAME'] ?? null,
    //                     'cost_center_amount' => $voucherData['COSTCENTREAMOUNT'] ?? null,
    //                 ]);

    //                 if ($tallyVoucher) {
    //                     $voucherHeadIds = $this->processLedgerEntriesForVoucher($tallyVoucher->id, $combinedLedgerEntries);
    //                     $voucherHeadIds = $this->processAccountingAllocationForVoucher($tallyVoucher->id, $accountingAllocations);
    //                     $inventoryEntriesWithId = $this->processInventoryEntriesForVoucher($tallyVoucher->id, $inventoryEntries);
    //                     $this->processBillAllocationsForVoucher($voucherHeadIds, $billAllocations);
    //                     $this->processBankAllocationsForVoucher($voucherHeadIds, $bankAllocations);
    //                     $this->processBatchAllocationsForVoucher($inventoryEntriesWithId, $batchAllocations);
    //                 } else {
    //                     throw new \Exception('Failed to create or update voucher item record.');
    //                 }
    //             }
    //         }

    //         return response()->json(['message' => 'Tally data saved successfully.', 'path' => $messagesPath]);
    //     } catch (\Exception $e) {
    //         Log::error('Error saving Tally voucher data: ' . $e->getMessage());
    //         return response()->json(['error' => 'Failed to save Tally data: ' . $e->getMessage()], 500);
    //     }
    // }

    // Method to delete related data of the voucher
    private function deleteRelatedVoucherData($voucherId, $voucherHeadIds, $inventoryEntriesWithId)
    {
        TallyVoucherHead::where('tally_voucher_id', $voucherId)->delete();
        TallyVoucherItem::where('tally_voucher_id', $voucherId)->delete();
        TallyBillAllocation::where('head_id', $voucherHeadIds)->delete();
        TallyBankAllocation::where('head_id', $voucherHeadIds)->delete();
        TallyBatchAllocation::where('item_id', $inventoryEntriesWithId)->delete();
        TallyVoucherAccAllocationHead::where('tally_voucher_id', $voucherId)->delete();
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
    
    private function processLedgerEntries(array $entries)
    {
        $ledgerEntries = [];
        foreach ($entries as $entry) {
            if (isset($entry['LEDGERNAME'], $entry['AMOUNT'])) {
                $ledgerName = htmlspecialchars_decode($entry['LEDGERNAME']);
                $amount = $entry['AMOUNT'];
                $entryType = $amount < 0 ? "debit" : "credit";
                $ledgerGuid = TallyLedger::where('language_name', $ledgerName)->value('guid');
    
                $ledgerEntries[] = [
                    'ledger_name' => $ledgerName,
                    'amount' => $amount,
                    'entry_type' => $entryType,
                    'ledger_guid' => $ledgerGuid,
                    'isdeemedpositive' => $entry['ISDEEMEDPOSITIVE'],
                ];
            } else {
                Log::error('Missing or invalid LEDGERNAME or AMOUNT in LEDGERENTRIES.LIST entry: ' . json_encode($entry));
            }
        }
        return $ledgerEntries;
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
                // Use a regular expression to extract the numeric part
                if (preg_match('/\d+/', $billedQtyString, $matches)) {
                    $billed_qty = $matches[0]; // $matches[0] contains the numeric part
                }
            }

            // Convert $billed_qty to a float or integer if needed
            $billed_qty = is_numeric($billed_qty) ? (float) $billed_qty : null;


    
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
                'amount' => $entry['AMOUNT'] ?? null,
                'gst_hsn_name' => $entry['GSTHSNNAME'] ?? null,
                'discount' => $entry['DISCOUNT'] ?? null,
                'igst_rate' => $igstRate,
            ];
        }


        // Log::info('Processed inventory entries: ', $inventoryEntries);
        return $inventoryEntries;
    }
    
    private function processLedgerEntriesForVoucher($voucherId, array $entries)
    {
        $voucherHeadIds = [];
        foreach ($entries as $entry) {
            $voucherHead = TallyVoucherHead::updateOrCreate(
                [
                    'tally_voucher_id' => $voucherId,
                    'ledger_name' => $entry['ledger_name'],
                ],
                [
                    'amount' => $entry['amount'],
                    'entry_type' => $entry['entry_type'],
                    'ledger_guid' => $entry['ledger_guid'],
                    'isdeemedpositive' => $entry['isdeemedpositive'],
                ]
            );
            $voucherHeadIds[] = [
                'id' => $voucherHead->id,
                'ledger_name' => $entry['ledger_name'],
            ];
        }
        return $voucherHeadIds;
    }
    
    private function processInventoryEntriesForVoucher($voucherId, array $entries)
    {
        $inventoryEntriesWithId = [];
    
        foreach ($entries as $item) {
            // Create a new entry without checking for uniqueness
            $newEntry = TallyVoucherItem::create([
                'tally_voucher_id' => $voucherId,
                'stock_item_name' => $item['stock_item_name'],
                'billed_qty' => $item['billed_qty'],
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
            ]);
    
            // Add the newly created entry to the result list
            $inventoryEntriesWithId[] = [
                'id' => $newEntry->id,
                'stock_item_name' => $item['stock_item_name'],
            ];
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
                                        'head_id' => $voucherHead['id'],
                                        'name' => $bill['NAME'],
                                    ],
                                    [
                                        'billamount' => $bill['AMOUNT'],
                                        'yearend' => $bill['YEAREND'] ?? null, // Use null if YEAREND is not present
                                        'billtype' => $bill['BILLTYPE'] ?? null, // Use null if BILLTYPE is not present
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
                                'head_id' => $voucherHead['id'] ?? null
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
                            'head_id' => $voucherHead['id'] ?? null
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
    
    private function sanitizeDate($date)
    {
        // Check if date is valid, if not return null or a default value
        if (empty($date) || !strtotime($date)) {
            return null; // or you can return a default value like '0000-00-00'
        }
    
        // Format date to 'Y-m-d' or other desired format
        return date('Y-m-d', strtotime($date));
    }

    private function processBatchAllocationsForVoucher(array $inventoryEntriesWithId, array $batchAllocations)
    {
        foreach ($inventoryEntriesWithId as $inventoryEntries) {
            $stockItemName = $inventoryEntries['stock_item_name'];
            if (isset($batchAllocations[$stockItemName]) && is_array($batchAllocations[$stockItemName])) {
                foreach ($batchAllocations[$stockItemName] as $batch) {
                    if (isset($batch['BATCHNAME'], $batch['AMOUNT'])) {
                        TallyBatchAllocation::updateOrCreate(
                            [
                            'item_id' => $inventoryEntries['id'],
                            'batch_name' => $batch['BATCHNAME'],
                            'godown_name' => $batch['GODOWNNAME'] ?? null,
                            'destination_godown_name' => $batch['DESTINATIONGODOWNNAME'] ?? null,
                            'amount' => $batch['AMOUNT'],
                            'actual_qty' => $batch['ACTUALQTY'] ?? null,
                            'billed_qty' => $batch['BILLEDQTY'] ?? null,
                            'order_no' => $batch['ORDERNO'] ?? null,
                            'batch_physical_diff' => $batch['BATCHPHYSDIFF'] ?? null,
                            ]
                        );
                    }
                }
            }
        }
    }

    private function processAccountingAllocations(array $entries)
    {
        $ledgerEntries = [];
        foreach ($entries as $entry) {
            // No condition check, process all entries
            $ledgerName = htmlspecialchars_decode($entry['LEDGERNAME'] ?? '');
            $amount = $entry['AMOUNT'] ?? 0;
            $entryType = $amount < 0 ? "debit" : "credit";
            $ledgerGuid = TallyLedger::where('language_name', $ledgerName)->value('guid');
    
            // Add entry to the array regardless of missing ledger GUID or amount
            $ledgerEntries[] = [
                'ledger_name' => $ledgerName,
                'amount' => $amount,
                'entry_type' => $entryType,
                'ledger_guid' => $ledgerGuid,
                'isdeemedpositive' => $entry['ISDEEMEDPOSITIVE'] ?? false,
            ];
        }
        return $ledgerEntries;
    }
    
    private function processAccountingAllocationForVoucher($voucherId, array $entries)
    {
        $voucherHeadIds = [];
        foreach ($entries as $entry) {
            // Create a new record for each entry, allowing duplicates
            $voucherHead = TallyVoucherAccAllocationHead::Create([
                'tally_voucher_id' => $voucherId,
                'ledger_name' => $entry['ledger_name'],
                'amount' => $entry['amount'],
                'entry_type' => $entry['entry_type'],
                'ledger_guid' => $entry['ledger_guid'],
                'isdeemedpositive' => $entry['isdeemedpositive'],
            ]);
    
            $voucherHeadIds[] = [
                'id' => $voucherHead->id,
                'ledger_name' => $entry['ledger_name'],
            ];
        }
        return $voucherHeadIds;
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
}
