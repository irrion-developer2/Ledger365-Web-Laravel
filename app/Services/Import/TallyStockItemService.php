<?php

namespace App\Services\Import;

use Carbon\Carbon;
use App\Models\TallyItem;
use App\Models\TallyUnit;
use App\Models\TallyGodown;
use App\Models\TallyCompany;
use App\Models\TallyItemGroup;
use App\Services\TallyLicenseCheck;
use Illuminate\Support\Facades\Log;

class TallyStockItemService
{
    protected $tallyLicenseCheck;

    public function __construct(TallyLicenseCheck $tallyLicenseCheck)
    {
        $this->tallyLicenseCheck = $tallyLicenseCheck;
    }

    public function importStockItemJson($request)
    {
        try {

            $this->tallyLicenseCheck->validateLicenseNumber($request);

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

            $result = $this->tallyLicenseCheck->findTallyMessage($data);

            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }

            $messagesPath = $result['path'];
            $messages = $result['value'];

            $unitCount = 0;
            $godownCount = 0;
            $stockGroupCount = 0;
            $stockItemCount = 0;

            $unitIds = [];

            foreach ($messages as $message) {
                if (isset($message['UNIT'])) {
                    $unitData = $message['UNIT'];
                    // Log::info('Unit Data:', ['unitData' => $unitData]);

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

                    try {
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
                        if ($tallyUnit) {
                            $unitCount++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error creating unit record: ' . $e->getMessage(), [
                            'unitData' => $unitData,
                            'companyId' => $companyId,
                        ]);
                    }

                    if (!$tallyUnit) {
                        throw new \Exception('Failed to create or update tally unit record.');
                    }
                    $unitIds[$name] = $tallyUnit->unit_id;
                }
            }

            foreach ($messages as $message) {
                if (isset($message['GODOWN'])) {
                    $godownData = $message['GODOWN'];
                    // Log::info('Godown Data:', ['godownData' => $godownData]);

                    $nameField = $godownData['LANGUAGENAME.LIST']['NAME.LIST']['NAME'] ?? null;
                    if (is_array($nameField)) {
                        $nameField = implode(', ', $nameField);
                    }

                    $guid = $godownData['GUID'] ?? null;
                    $companyGuid = substr($guid, 0, 36);

                    $company = TallyCompany::where('company_guid', $companyGuid)->first();

                    if (!$company) {
                        Log::error('Company GUID not found in tally_companies: ' . $companyGuid);
                        continue;
                    }

                    $companyId = $company->company_id;
                    $companyIds[$companyId] = true;

                    try {
                        $tallyGodown = TallyGodown::updateOrCreate(
                            ['godown_guid' => $godownData['GUID'] ?? null],
                            [
                                'company_id' => $companyId,
                                'parent' => $godownData['PARENT'] ?? null,
                                'alter_id' => $godownData['ALTERID'] ?? null,
                                'godown_name' => $nameField,
                            ]
                        );
                        if ($tallyGodown) {
                            $godownCount++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error creating godown record: ' . $e->getMessage(), [
                            'godownData' => $godownData,
                            'companyId' => $companyId,
                        ]);
                    }

                    if (!$tallyGodown) {
                        throw new \Exception('Failed to create or update tally Godown record.');
                    }
                }
            }

            foreach ($messages as $message) {
                if (isset($message['STOCKGROUP'])) {
                    $stockGroupData = $message['STOCKGROUP'];
                    // Log::info('STOCKGROUP Data:', ['stockGroupData' => $stockGroupData]);

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

                    try {
                        $tallystockGroup = TallyItemGroup::updateOrCreate(
                            ['item_group_guid' => $stockGroupData['GUID'] ?? null],
                            [
                                'company_id' => $companyId,
                                'parent' => $stockGroupData['PARENT'] ?? null,
                                'alter_id' => $stockGroupData['ALTERID'] ?? null,
                                'item_group_name' => $nameField,
                            ]
                        );
                        if ($tallystockGroup) {
                            $stockGroupCount++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error creating Stock Group record: ' . $e->getMessage(), [
                            'stockGroupData' => $stockGroupData,
                            'companyId' => $companyId,
                        ]);
                    }

                    if (!$tallystockGroup) {
                        throw new \Exception('Failed to create or update tally Stock Group record.');
                    }
                }
            }

            foreach ($messages as $message) {
                if (isset($message['STOCKITEM'])) {
                    $stockItemData = $message['STOCKITEM'];
                    // Log::info('Stock Item Data:', ['stockItemData' => $stockItemData]);

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

                    $itemParentName = $stockItemData['PARENT'] ?? null;
                    $itemGroup = TallyItemGroup::where('item_group_name', $itemParentName)
                        ->where('company_id', $companyId)->first();
                    $itemGroupIds = $itemGroup ? $itemGroup->item_group_id : null;

                    $unitName = $stockItemData['BASEUNITS'] ?? null;
                    $unitId = TallyUnit::where('unit_name', $unitName)->where('company_id', $companyId)->first();
                    $unitIds = $unitId ? $unitId->unit_id : null;

                    $itemName = $stockItemData['NAME'] ?? null;
                    if (strlen($itemName) > 255) {
                        $itemName = substr($itemName, 0, 255);
                    }

                    $openingRate = isset($stockItemData['OPENINGRATE'])
                        ? (is_numeric($cleaned = preg_replace('/[^-0-9.]/', '', $stockItemData['OPENINGRATE']))
                            ? (float)$cleaned
                            : 0)
                        : 0;

                    $openingValue = isset($stockItemData['OPENINGVALUE'])
                        ? (is_numeric($cleaned = preg_replace('/[^-0-9.]/', '', $stockItemData['OPENINGVALUE']))
                            ? (float)$cleaned
                            : 0)
                        : 0;
                    $openingBalance = isset($stockItemData['OPENINGBALANCE'])
                        ? (is_numeric($cleaned = preg_replace('/[^-0-9.]/', '', $stockItemData['OPENINGBALANCE']))
                            ? (float)$cleaned
                            : 0)
                        : 0;

                    // if opening value is negative then opening balance will be negative make sure
                    // opening balance is negative
                    if ($openingValue < 0 && $openingBalance > 0) {
                        $openingBalance = -$openingBalance;
                    }

                    try {
                        $tallyStockItem = TallyItem::updateOrCreate(
                            ['item_guid' => $stockItemData['GUID'] ?? null],
                            [
                                'company_id' => $companyId,
                                'item_group_id' => $itemGroupIds,
                                'unit_id' => $unitIds,
                                'item_name' => $itemName,
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
                                'opening_balance' => $openingBalance,
                                'opening_value' => $openingValue,
                                'opening_rate' => $openingRate,
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
                        if ($tallyStockItem) {
                            $stockItemCount++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error creating Stock Item record: ' . $e->getMessage(), [
                            'stockItemData' => $stockItemData,
                            'companyId' => $companyId,
                        ]);
                    }

                    if (!$tallyStockItem) {
                        throw new \Exception('Failed to create or update tally stock item record.');
                    }
                }
            }

            return response()->json([
                'message' => 'Stock item saved',
                'units_inserted' => $unitCount,
                'godowns_inserted' => $godownCount,
                'stock_groups_inserted' => $stockGroupCount,
                'stock_items_inserted' => $stockItemCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error importing stock items:', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

}
