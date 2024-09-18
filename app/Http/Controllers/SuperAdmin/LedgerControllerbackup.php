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
use App\Models\TallyVoucherEntry;
use App\Models\TallyVoucherItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 

class LedgerController extends Controller
{

    public function companyJsonImport(Request $request)
    {
        try {
            $jsonData = $request->getContent();
            $fileName = 'tally_company_data_' . date('YmdHis') . '.json';
            $jsonFilePath = storage_path('app/' . $fileName);
            file_put_contents($jsonFilePath, $jsonData);
            $jsonData = file_get_contents($jsonFilePath);
            $data = json_decode($jsonData, true);

            if (!isset($data['ENVELOPE']['BODY']['IMPORTDATA']['REQUESTDATA']['TALLYMESSAGE'])) {
                throw new \Exception('Invalid JSON structure.');
            }

            $messages = $data['ENVELOPE']['BODY']['IMPORTDATA']['REQUESTDATA']['TALLYMESSAGE'];

            //Track company GUIDs to ensure they are inserted only once
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
            
            return response()->json(['message' => 'Tally data saved successfully.']);

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

            if (!isset($data['ENVELOPE']['BODY']['IMPORTDATA']['REQUESTDATA']['TALLYMESSAGE'])) {
                throw new \Exception('Invalid JSON structure.');
            }

            $messages = $data['ENVELOPE']['BODY']['IMPORTDATA']['REQUESTDATA']['TALLYMESSAGE'];

            // Track company GUIDs to ensure they are inserted only once
            // $companyGuids = TallyCompany::pluck('guid')->toArray();
            // Log::info('Company GUIDs in Database:', ['companyGuids' => $companyGuids]);

            // foreach ($messages as $message) {
            //     if (isset($message['COMPANY'])) {
            //         $companyData = $message['COMPANY']['REMOTECMPINFO.LIST'];
            //         $companyGuid = $companyData['NAME'];

            //         if (!in_array($companyGuid, $companyGuids)) {
            //             $company = TallyCompany::create([
            //                 'guid' => $companyGuid,
            //                 'name' => $companyData['REMOTECMPNAME'] ?? null,
            //                 'state' => $companyData['REMOTECMPSTATE'] ?? null,
            //             ]);

            //             if (!$company) {
            //                 throw new \Exception('Failed to create tally company record.');
            //             }

            //             $companyGuids[] = $companyGuid;
            //         }
            //     }
            // }

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

                        // $nameField = $ledgerData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? null;
                        // if (is_array($nameField)) {
                        //     $nameField = implode(', ', $nameField);
                        // }

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
                        $languageName = $nameField[0] ?? null;
                        $alias = $nameField[1] ?? null;

                        $tallyLedger = TallyLedger::updateOrCreate(
                            ['guid' => $guid],
                            [
                                'company_guid' => $companyGuid,
                                'parent' => $ledgerData['PARENT'] ?? null,
                                'tax_classification_name' => html_entity_decode($ledgerData['TAXCLASSIFICATIONNAME'] ?? null),
                                'tax_type' => $ledgerData['TAXTYPE'] ?? null,
                                'gst_type' => html_entity_decode($ledgerData['GSTTYPE'] ?? null),
                                'appropriate_for' => html_entity_decode($ledgerData['APPROPRIATEFOR'] ?? null),
                                'party_gst_in' => $ledgerData['PARTYGSTIN'] ?? null,
                                'service_category' => html_entity_decode($ledgerData['SERVICECATEGORY'] ?? null),
                                'gst_registration_type' => $ledgerData['GSTREGISTRATIONTYPE'] ?? null,
                                'excise_ledger_classification' => html_entity_decode($ledgerData['EXCISELEDGERCLASSIFICATION'] ?? null),
                                'excise_duty_type' => html_entity_decode($ledgerData['EXCISEDUTYTYPE'] ?? null),
                                'excise_nature_of_purchase' => html_entity_decode($ledgerData['EXCISENATUREOFPURCHASE'] ?? null),
                                'ledger_fbt_category' => html_entity_decode($ledgerData['LEDGERFBTCATEGORY'] ?? null),
                                'is_bill_wise_on' => $ledgerData['ISBILLWISEON'] ?? null,
                                'is_cost_centres_on' => $ledgerData['ISCOSTCENTRESON'] ?? null,
                                'alter_id' => $ledgerData['ALTERID'] ?? null,
                                'language_name' => $languageName,
                                'alias' => $alias,
                                'language_id' => $ledgerData['LANGUAGENAME.LIST']['LANGUAGEID'] ?? null,
                                'applicable_from' => $applicableFrom,
                                'ledger_gst_registration_type' => $ledgerData['LEDGSTREGDETAILS.LIST']['GSTREGISTRATIONTYPE'] ?? null,
                                'gst_in' => $ledgerData['LEDGSTREGDETAILS.LIST']['GSTIN'] ?? null,
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

            return response()->json(['message' => 'Tally data saved successfully.']);

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

            if (!isset($data['ENVELOPE']['BODY']['IMPORTDATA']['REQUESTDATA']['TALLYMESSAGE'])) {
                throw new \Exception('Invalid JSON structure.');
            }

            $messages = $data['ENVELOPE']['BODY']['IMPORTDATA']['REQUESTDATA']['TALLYMESSAGE'];

        
            foreach ($messages as $message) {
                if (isset($message['UNIT'])) {
                    $unitData = $message['UNIT'];
                    Log::info('Unit Data:', ['unitData' => $unitData]);

                    // Extract REPORTINGUQCDETAILS.LIST
                    $reportingUQCDetails = $unitData['REPORTINGUQCDETAILS.LIST'] ?? [];
                    $reportingUQCName = $reportingUQCDetails['REPORTINGUQCNAME'] ?? null;
                    $applicableFrom = $reportingUQCDetails['APPLICABLEFROM'] ?? null;

                    $tallyUnit = TallyUnit::updateOrCreate(
                        ['guid' => $unitData['GUID'] ?? null],
                        [
                            'name' => $unitData['NAME'] ?? null,
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
                    $languageName = $nameField[0] ?? null;
                    $alias = $nameField[1] ?? null;

                    $tallyStockItem = TallyItem::updateOrCreate(
                        ['guid' => $stockItemData['GUID'] ?? null],
                        [
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
                            'gst_details' => json_encode($stockItemData['GSTDETAILS.LIST'] ?? []),
                            'hsn_details' => json_encode($stockItemData['HSNDETAILS.LIST'] ?? []),
                            'language_name' => $languageName,
                            'alias' => $alias,
                            'language_id' => $stockItemData['LANGUAGENAME.LIST']['LANGUAGEID'] ?? null,
                            'batch_allocations' => json_encode($stockItemData['BATCHALLOCATIONS.LIST'] ?? []),
                        ]
                    );

                    if (!$tallyStockItem) {
                        throw new \Exception('Failed to create or update tally Stock Item record.');
                    }
                }
            }


            return response()->json(['message' => 'Tally data saved successfully.']);

        } catch (\Exception $e) {
            Log::error('Error importing data: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function voucherJsonImport(Request $request)
    {
        try {
            $jsonData = $request->getContent();
            $fileName = 'tally_voucher_data_' . date('YmdHis') . '.json';
            $jsonFilePath = storage_path('app/' . $fileName);
            file_put_contents($jsonFilePath, $jsonData);
    
            $jsonData = file_get_contents($jsonFilePath);
            $data = json_decode($jsonData, true);
    
            if (!isset($data['ENVELOPE']['BODY']['IMPORTDATA']['REQUESTDATA']['TALLYMESSAGE'])) {
                throw new \Exception('Invalid JSON structure.');
            }
    
            $messages = $data['ENVELOPE']['BODY']['IMPORTDATA']['REQUESTDATA']['TALLYMESSAGE'];


            foreach ($messages as $message) {
                if (isset($message['VOUCHER'])) {
                        $voucherData = $message['VOUCHER'];
    
                        $partyLedgerName = $voucherData['PARTYLEDGERNAME'] ?? $voucherData['PARTYNAME'] ?? null;
            
                        // Handle LEDGERENTRIES.LIST entries
                        $ledgerEntries = [];
                        if (isset($voucherData['LEDGERENTRIES.LIST']) && is_array($voucherData['LEDGERENTRIES.LIST'])) {
                            foreach ($voucherData['LEDGERENTRIES.LIST'] as $entry) {
                                if (isset($entry['LEDGERNAME'], $entry['AMOUNT'])) {
                                    $ledgerName = $entry['LEDGERNAME'];
                                    $amount = $entry['AMOUNT'];
            
                                    // Save or process each ledger entry as needed
                                    $ledgerEntries[] = [
                                        'ledger_name' => $ledgerName,
                                        'amount' => $amount,
                                    ];
                                } else {
                                    Log::error('Missing or invalid LEDGERNAME or AMOUNT in LEDGERENTRIES.LIST entry: ' . json_encode($entry));
                                }
                            }
                        }
            
                        // Handle ALLINVENTORYENTRIES.LIST entries
                        $inventoryEntries = [];
                        if (isset($voucherData['ALLINVENTORYENTRIES.LIST']) && is_array($voucherData['ALLINVENTORYENTRIES.LIST'])) {
                            foreach ($voucherData['ALLINVENTORYENTRIES.LIST'] as $inventoryEntry) {
                                $rateString = $inventoryEntry['RATE'] ?? null;
                                $unit = null;
            
                                // Extract rate and unit from rateString
                                if ($rateString !== null) {
                                    // Split rateString by "/"
                                    $parts = explode('/', $rateString);
                                    if (count($parts) === 2) {
                                        $rate = trim($parts[0]); // Extract rate part
                                        $unit = trim($parts[1]); // Extract unit part
                                    } else {
                                        $rate = $rateString; // Use the whole string as rate if no "/" found
                                    }
                                } else {
                                    $rate = null;
                                }
            
                                // Save or process each inventory entry as needed
                                $inventoryEntries[] = [
                                    'stock_item_name' => $inventoryEntry['STOCKITEMNAME'] ?? null,
                                    'gst_taxability' => $inventoryEntry['GSTOVRDNTAXABILITY'] ?? null,
                                    'gst_source_type' => $inventoryEntry['GSTSOURCETYPE'] ?? null,
                                    'gst_item_source' => $inventoryEntry['GSTITEMSOURCE'] ?? null,
                                    'gst_ledger_source' => $inventoryEntry['GSTLEDGERSOURCE'] ?? null,
                                    'hsn_source_type' => $inventoryEntry['HSNSOURCETYPE'] ?? null,
                                    'hsn_item_source' => $inventoryEntry['HSNLEDGERSOURCE'] ?? null,
                                    'gst_rate_infer_applicability' => $inventoryEntry['GSTRATEINFERAPPLICABILITY'] ?? null,
                                    'gst_hsn_infer_applicability' => $inventoryEntry['GSTHSNINFERAPPLICABILITY'] ?? null,
                                    'rate' => $rate,
                                    'unit' => $unit, // Save unit separately
                                    // Add more fields as needed
                                ];
                            }
                        }
            
                        $guid = $voucherData['GUID'] ?? null;
                        $companyGuid = substr($guid, 0, 36);
            
                        // Check if the voucher already exists
                        $tallyVoucher = TallyVoucher::where('guid', $guid)->first();
            
                        if ($tallyVoucher) {
                            // Update existing record if needed
                            $tallyVoucher->update([
                                'company_guid' => $companyGuid,
                                'voucher_type' => $voucherData['VOUCHERTYPENAME'] ?? null,
                                'is_cancelled' => $voucherData['ISCANCELLED'] ?? null,
                                'alter_id' => $voucherData['ALTERID'] ?? null,
                                'party_ledger_name' => $partyLedgerName,
                                'voucher_number' => $voucherData['VOUCHERNUMBER'] ?? null,
                                'voucher_date' => $voucherData['DATE'] ?? null,
                                'place_of_supply' => $voucherData['PLACEOFSUPPLY'] ?? null,
                                'country_of_residense' => $voucherData['COUNTRYOFRESIDENCE'] ?? null,
                                'gst_registration_type' => $voucherData['GSTREGISTRATIONTYPE'] ?? null,
                                'numbering_style' => $voucherData['NUMBERINGSTYLE'] ?? null,
                            ]);
                        } else {
                            // Create new record if not exists
                            $tallyVoucher = TallyVoucher::create([
                                'guid' => $guid,
                                'company_guid' => $companyGuid,
                                'voucher_type' => $voucherData['VOUCHERTYPENAME'] ?? null,
                                'is_cancelled' => $voucherData['ISCANCELLED'] ?? null,
                                'alter_id' => $voucherData['ALTERID'] ?? null,
                                'party_ledger_name' => $partyLedgerName,
                                'voucher_number' => $voucherData['VOUCHERNUMBER'] ?? null,
                                'voucher_date' => $voucherData['DATE'] ?? null,
                                'place_of_supply' => $voucherData['PLACEOFSUPPLY'] ?? null,
                                'country_of_residense' => $voucherData['COUNTRYOFRESIDENCE'] ?? null,
                                'gst_registration_type' => $voucherData['GSTREGISTRATIONTYPE'] ?? null,
                                'numbering_style' => $voucherData['NUMBERINGSTYLE'] ?? null,
                            ]);
                        }
            
                        if (!$tallyVoucher) {
                            throw new \Exception('Failed to create or update voucher item record.');
                        }
            
                        // Handle ledger entries
                        foreach ($ledgerEntries as $entry) {
                            TallyVoucherEntry::updateOrCreate(
                                [
                                    'tally_voucher_id' => $tallyVoucher->id,
                                    'ledger_name' => $entry['ledger_name'],
                                ],
                                [
                                    'amount' => $entry['amount'],
                                ]
                            );
                        }
            
                        // Create or update TallyVoucherItem records for ALLINVENTORYENTRIES.LIST
                        foreach ($inventoryEntries as $item) {
                            TallyVoucherItem::updateOrCreate(
                                [
                                    'tally_voucher_id' => $tallyVoucher->id,
                                    'stock_item_name' => $item['stock_item_name'],
                                    'rate' => $item['rate'],
                                    'unit' => $item['unit'],
                                ],
                                [
                                    'gst_taxability' => $item['gst_taxability'],
                                    'gst_source_type' => $item['gst_source_type'],
                                    'gst_item_source' => $item['gst_item_source'],
                                    'gst_ledger_source' => $item['gst_ledger_source'],
                                    'hsn_source_type' => $item['hsn_source_type'],
                                    'hsn_item_source' => $item['hsn_item_source'],
                                    'gst_rate_infer_applicability' => $item['gst_rate_infer_applicability'],
                                    'gst_hsn_infer_applicability' => $item['gst_hsn_infer_applicability'],
                                ]
                            );
                        }

                    if (!$tallyVoucher) {
                        throw new \Exception('Failed to create or update tally voucher record.');
                    }
                }
            }
    
            // foreach ($messages as $message) {
            //     if (!isset($message['VOUCHER'])) {
            //         throw new \Exception('Invalid message structure.');
            //     }
    
            //     $voucherData = $message['VOUCHER'];
    
            //     $partyLedgerName = $voucherData['PARTYLEDGERNAME'] ?? $voucherData['PARTYNAME'] ?? null;
    
            //     // Handle LEDGERENTRIES.LIST entries
            //     $ledgerEntries = [];
            //     if (isset($voucherData['LEDGERENTRIES.LIST']) && is_array($voucherData['LEDGERENTRIES.LIST'])) {
            //         foreach ($voucherData['LEDGERENTRIES.LIST'] as $entry) {
            //             if (isset($entry['LEDGERNAME'], $entry['AMOUNT'])) {
            //                 $ledgerName = $entry['LEDGERNAME'];
            //                 $amount = $entry['AMOUNT'];
    
            //                 // Save or process each ledger entry as needed
            //                 $ledgerEntries[] = [
            //                     'ledger_name' => $ledgerName,
            //                     'amount' => $amount,
            //                 ];
            //             } else {
            //                 Log::error('Missing or invalid LEDGERNAME or AMOUNT in LEDGERENTRIES.LIST entry: ' . json_encode($entry));
            //             }
            //         }
            //     }
    
            //     // Handle ALLINVENTORYENTRIES.LIST entries
            //     $inventoryEntries = [];
            //     if (isset($voucherData['ALLINVENTORYENTRIES.LIST']) && is_array($voucherData['ALLINVENTORYENTRIES.LIST'])) {
            //         foreach ($voucherData['ALLINVENTORYENTRIES.LIST'] as $inventoryEntry) {
            //             $rateString = $inventoryEntry['RATE'] ?? null;
            //             $unit = null;
    
            //             // Extract rate and unit from rateString
            //             if ($rateString !== null) {
            //                 // Split rateString by "/"
            //                 $parts = explode('/', $rateString);
            //                 if (count($parts) === 2) {
            //                     $rate = trim($parts[0]); // Extract rate part
            //                     $unit = trim($parts[1]); // Extract unit part
            //                 } else {
            //                     $rate = $rateString; // Use the whole string as rate if no "/" found
            //                 }
            //             } else {
            //                 $rate = null;
            //             }
    
            //             // Save or process each inventory entry as needed
            //             $inventoryEntries[] = [
            //                 'stock_item_name' => $inventoryEntry['STOCKITEMNAME'] ?? null,
            //                 'gst_taxability' => $inventoryEntry['GSTOVRDNTAXABILITY'] ?? null,
            //                 'gst_source_type' => $inventoryEntry['GSTSOURCETYPE'] ?? null,
            //                 'gst_item_source' => $inventoryEntry['GSTITEMSOURCE'] ?? null,
            //                 'gst_ledger_source' => $inventoryEntry['GSTLEDGERSOURCE'] ?? null,
            //                 'hsn_source_type' => $inventoryEntry['HSNSOURCETYPE'] ?? null,
            //                 'hsn_item_source' => $inventoryEntry['HSNLEDGERSOURCE'] ?? null,
            //                 'gst_rate_infer_applicability' => $inventoryEntry['GSTRATEINFERAPPLICABILITY'] ?? null,
            //                 'gst_hsn_infer_applicability' => $inventoryEntry['GSTHSNINFERAPPLICABILITY'] ?? null,
            //                 'rate' => $rate,
            //                 'unit' => $unit, // Save unit separately
            //                 // Add more fields as needed
            //             ];
            //         }
            //     }
    
            //     $guid = $voucherData['GUID'] ?? null;
            //     $companyGuid = substr($guid, 0, 36);
    
            //     // Check if the voucher already exists
            //     $tallyVoucher = TallyVoucher::where('guid', $guid)->first();
    
            //     if ($tallyVoucher) {
            //         // Update existing record if needed
            //         $tallyVoucher->update([
            //             'company_guid' => $companyGuid,
            //             'voucher_type' => $voucherData['VOUCHERTYPENAME'] ?? null,
            //             'is_cancelled' => $voucherData['ISCANCELLED'] ?? null,
            //             'alter_id' => $voucherData['ALTERID'] ?? null,
            //             'party_ledger_name' => $partyLedgerName,
            //             'voucher_number' => $voucherData['VOUCHERNUMBER'] ?? null,
            //             'voucher_date' => $voucherData['DATE'] ?? null,
            //             'place_of_supply' => $voucherData['PLACEOFSUPPLY'] ?? null,
            //             'country_of_residense' => $voucherData['COUNTRYOFRESIDENCE'] ?? null,
            //             'gst_registration_type' => $voucherData['GSTREGISTRATIONTYPE'] ?? null,
            //             'numbering_style' => $voucherData['NUMBERINGSTYLE'] ?? null,
            //         ]);
            //     } else {
            //         // Create new record if not exists
            //         $tallyVoucher = TallyVoucher::create([
            //             'guid' => $guid,
            //             'company_guid' => $companyGuid,
            //             'voucher_type' => $voucherData['VOUCHERTYPENAME'] ?? null,
            //             'is_cancelled' => $voucherData['ISCANCELLED'] ?? null,
            //             'alter_id' => $voucherData['ALTERID'] ?? null,
            //             'party_ledger_name' => $partyLedgerName,
            //             'voucher_number' => $voucherData['VOUCHERNUMBER'] ?? null,
            //             'voucher_date' => $voucherData['DATE'] ?? null,
            //             'place_of_supply' => $voucherData['PLACEOFSUPPLY'] ?? null,
            //             'country_of_residense' => $voucherData['COUNTRYOFRESIDENCE'] ?? null,
            //             'gst_registration_type' => $voucherData['GSTREGISTRATIONTYPE'] ?? null,
            //             'numbering_style' => $voucherData['NUMBERINGSTYLE'] ?? null,
            //         ]);
            //     }
    
            //     if (!$tallyVoucher) {
            //         throw new \Exception('Failed to create or update voucher item record.');
            //     }
    
            //     // Handle ledger entries
            //     foreach ($ledgerEntries as $entry) {
            //         TallyVoucherEntry::updateOrCreate(
            //             [
            //                 'tally_voucher_id' => $tallyVoucher->id,
            //                 'ledger_name' => $entry['ledger_name'],
            //             ],
            //             [
            //                 'amount' => $entry['amount'],
            //             ]
            //         );
            //     }
    
            //     // Create or update TallyVoucherItem records for ALLINVENTORYENTRIES.LIST
            //     foreach ($inventoryEntries as $item) {
            //         TallyVoucherItem::updateOrCreate(
            //             [
            //                 'tally_voucher_id' => $tallyVoucher->id,
            //                 'stock_item_name' => $item['stock_item_name'],
            //                 'rate' => $item['rate'],
            //                 'unit' => $item['unit'],
            //             ],
            //             [
            //                 'gst_taxability' => $item['gst_taxability'],
            //                 'gst_source_type' => $item['gst_source_type'],
            //                 'gst_item_source' => $item['gst_item_source'],
            //                 'gst_ledger_source' => $item['gst_ledger_source'],
            //                 'hsn_source_type' => $item['hsn_source_type'],
            //                 'hsn_item_source' => $item['hsn_item_source'],
            //                 'gst_rate_infer_applicability' => $item['gst_rate_infer_applicability'],
            //                 'gst_hsn_infer_applicability' => $item['gst_hsn_infer_applicability'],
            //             ]
            //         );
            //     }
            // }
    
            return response()->json(['message' => 'Tally voucher data saved successfully.']);
    
        } catch (\Exception $e) {
            Log::error('Error importing data: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
