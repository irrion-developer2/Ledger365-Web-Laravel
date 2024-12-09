<?php

namespace App\Services\Import;

use App\Models\TallyItem;
use App\Models\TallyUnit;
use App\Models\TallyGodown;
use App\Models\TallyLedger;
use App\Models\TallyCompany;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherItem;
use App\Models\TallyVoucherType;
use App\Models\TallyBankAllocation;
use App\Models\TallyBillAllocation;
use App\Services\TallyLicenseCheck;
use Illuminate\Support\Facades\Log;
use App\Models\TallyBatchAllocation;
use App\Repositories\Contracts\TallyCompanyRepositoryInterface;

class TallyVoucherService
{
    protected $tallyLicenseCheck;

    public function __construct(TallyLicenseCheck $tallyLicenseCheck)
    {
        $this->tallyLicenseCheck = $tallyLicenseCheck;
    }

    public function importVoucherJson($request)
    {
        try {

            $this->tallyLicenseCheck->validateLicenseNumber($request);

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

            $result = $this->tallyLicenseCheck->findTallyMessage($data);

            if ($result === null) {
                throw new \Exception('TALLYMESSAGE key not found in the JSON data.');
            }

            $messagesPath = $result['path'];
            $messages = $result['value'];

            $voucherCount = 0;

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
                            $formattedDate = $this->tallyLicenseCheck->convertToDesiredDateFormat($note['BASICSHIPPINGDATE']);
                            $formattedShippingDates[] = $formattedDate;
                            $deliveryNotes[] = $note['BASICSHIPDELIVERYNOTE'];
                        }
                    }
                    $deliveryNotesStr = implode(', ', $deliveryNotes);


                    $ledgerEntries = $this->tallyLicenseCheck->normalizeEntries($this->tallyLicenseCheck->ensureArray($voucherData['LEDGERENTRIES.LIST'] ?? []));
                    $allLedgerEntries = $this->tallyLicenseCheck->normalizeEntries($this->tallyLicenseCheck->ensureArray($voucherData['ALLLEDGERENTRIES.LIST'] ?? []));
                    $combinedLedgerEntries = array_merge($ledgerEntries, $allLedgerEntries);


                    $inventoryEntries = $this->tallyLicenseCheck->normalizeEntries($this->tallyLicenseCheck->ensureArray($voucherData['ALLINVENTORYENTRIES.LIST'] ?? []));
                    $accountingAllocations = [];
                    foreach ($inventoryEntries as $inventoryEntry) {
                        if (isset($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST'])) {
                            $accountingAllocations = array_merge($accountingAllocations, $this->tallyLicenseCheck->normalizeEntries($inventoryEntry['ACCOUNTINGALLOCATIONS.LIST']));
                        }
                    }
                    $accountingAllocations = $this->processAccountingAllocations($accountingAllocations, $companyId);


                    $batchAllocations = [];
                    foreach ($inventoryEntries as $inventoryEntry) {
                        if (isset($inventoryEntry['BATCHALLOCATIONS.LIST'])) {
                            $batchAllocations[$inventoryEntry['STOCKITEMNAME']] = $this->tallyLicenseCheck->normalizeEntries($inventoryEntry['BATCHALLOCATIONS.LIST']);
                        }
                    }


                    $billAllocations = [];
                    foreach ($combinedLedgerEntries as $ledgerEntry) {
                        if (isset($ledgerEntry['BILLALLOCATIONS.LIST'])) {
                            $billAllocations[$ledgerEntry['LEDGERNAME']] = $this->tallyLicenseCheck->normalizeEntries($ledgerEntry['BILLALLOCATIONS.LIST']);
                        }
                    }

                    $bankAllocations = [];
                    foreach ($combinedLedgerEntries as $ledgerEntry) {
                        if (isset($ledgerEntry['BANKALLOCATIONS.LIST'])) {
                            $bankAllocations[$ledgerEntry['LEDGERNAME']] = $this->tallyLicenseCheck->normalizeEntries($ledgerEntry['BANKALLOCATIONS.LIST']);
                        }
                    }

                    $voucherType = $voucherData['VOUCHERTYPENAME'] ?? null;
                    $voucherTypeId = TallyVoucherType::where('voucher_type_name', $voucherType)
                        ->where('company_Id', $companyId)
                        ->value('voucher_type_id');

                    // if vouchertypeid is null print query
                    if (!$voucherTypeId) {
                        Log::error('Voucher Type ID not found for voucher type: ' . $voucherType . ' and company ID: ' . $companyId);
                    }


                    
                    $narration = $voucherData['NARRATION'] ?? null;

                    if (is_array($narration)) {
                        Log::info('narration is an array', ['narration' => $narration]);
                        // Extract the value with the empty string key
                        $narration = $narration[""] ?? null;
                        Log::info('Extracted narration value', ['narration' => $narration]);
                    }

                    if (is_string($narration) && strlen($narration) > 255) {
                        $narration = substr($narration, 0, 255);
                    } else {
                        $narration = "";
                    }

                    // Log::info('jsonFilePath Data:', ['jsonFilePath' => $jsonFilePath]);

                    $tallyVoucher = TallyVoucher::updateOrCreate(
                        [
                            'voucher_guid' => $voucherData['GUID'],
                            'company_id' => $companyId
                        ],

                        [
                            'voucher_type_id' => $voucherTypeId,
                            // 'voucher_type' => $voucherData['VOUCHERTYPENAME'] ?? null,
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
                            'narration' => $narration,
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
                            'json_path' => $jsonFilePath,
                        ]
                    );

                    if ($tallyVoucher) {
                        $voucherCount++;
                    }

                    if (!$tallyVoucher) {
                        throw new \Exception('Failed to create or update tally Voucher record.');
                    }

                    $voucherHeadIds = $this->processLedgerEntries($voucherData, $tallyVoucher, $companyId);

                    $inventoryEntriesWithId = $this->processInventoryEntries($voucherData['ALLINVENTORYENTRIES.LIST'] ?? [], $voucherHeadIds, $companyId);

                    $this->processAccountingAllocationForVoucher($tallyVoucher->voucher_id, $accountingAllocations, $companyId);

                    if (!empty($inventoryEntriesWithId)) {
                        $this->processBatchAllocationsForVoucher($inventoryEntriesWithId, $batchAllocations, $companyId);
                    } else {
                        // Log::info('No inventory entries with ID found; skipping batch allocations.');
                    }

                    $this->processBillAllocationsForVoucher($voucherHeadIds, $billAllocations);
                    $this->processBankAllocationsForVoucher($voucherHeadIds, $bankAllocations);


                }
            }

            return response()->json([
                'message' => 'Vouchers saved',
                'vouchers_processed' => $voucherCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving Tally voucher data:', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'Failed to save Tally data', 'message' => $e->getMessage()], 500);
        }
    }

    private function processLedgerEntries(array $voucherData, TallyVoucher $tallyVoucher, $companyId)
    {
        $ledgerEntries = array_merge(
            $this->tallyLicenseCheck->normalizeEntries($voucherData['LEDGERENTRIES.LIST'] ?? []),
            $this->tallyLicenseCheck->normalizeEntries($voucherData['ALLLEDGERENTRIES.LIST'] ?? [])
        );

        $voucherHeadIds = [];

        foreach ($ledgerEntries as $ledgerEntry) {
            $ledgerName = htmlspecialchars_decode($ledgerEntry['LEDGERNAME'] ?? '');
            $amount = $ledgerEntry['AMOUNT'] ?? 0;
            $entryType = $amount < 0 ? "debit" : "credit";

            $ledgerId = TallyLedger::where('ledger_name', $ledgerName)
                ->where('company_id', $companyId)
                ->value('ledger_id');


            if (!$ledgerId) {
                Log::error('Ledger not found', [
                    'ledger_name' => $ledgerName,
                    'company_id' => $companyId,
                    'voucher_id' => $tallyVoucher->voucher_id ?? null,
                    'ledger_entry' => $ledgerEntry
                ]);
                // Optionally, you can throw an exception or create the ledger here
                // throw new \Exception("Ledger not found: $ledgerName for company_id: $companyId");
            }

            $ledgerHeadData = [
                'voucher_id' => $tallyVoucher->voucher_id,
                'amount' => $amount,
                'entry_type' => $entryType,
                'ledger_id' => $ledgerId,
                'is_party_ledger' => ($ledgerEntry['ISPARTYLEDGER'] ?? null) === 'Yes' ? true : false,
                'is_deemed_positive' => isset($ledgerEntry['ISDEEMEDPOSITIVE']) && $ledgerEntry['ISDEEMEDPOSITIVE'] === 'Yes',
            ];

            try {
                $voucherHead = TallyVoucherHead::updateOrCreate($ledgerHeadData);

                if ($voucherHead) {
                    $voucherHeadIds[] = $voucherHead->voucher_head_id;
                } else {
                    Log::error('Error saving Tally voucher data:', ['data' => $ledgerHeadData]);
                }
            } catch (\Exception $e) {
                Log::error('Exception while saving Tally voucher data:', [
                    'exception' => $e->getMessage(),
                    'data' => $ledgerHeadData
                ]);
            }
        }
        return $voucherHeadIds;
    }

    private function processAccountingAllocations(array $entries, $companyId)
    {
        $ledgerEntries = [];

        foreach ($entries as $entry) {
            if (isset($entry['LEDGERNAME'], $entry['AMOUNT'])) {
                $ledgerName = htmlspecialchars_decode($entry['LEDGERNAME']);
                $amount = $entry['AMOUNT'];
                $entryType = $amount < 0 ? "debit" : "credit";

                $ledgerId = TallyLedger::where('ledger_name', $ledgerName)
                    ->where('company_Id', $companyId)
                    ->value('ledger_id');


                if (!$ledgerId) {
                    Log::error('Ledger GUID not found in database for ledger: ' . $ledgerName);
                    continue;
                }

                if (isset($ledgerEntries[$ledgerName])) {
                    $ledgerEntries[$ledgerName]['amount'] += $amount;
                } else {
                    $ledgerEntries[$ledgerName] = [
                        'amount' => $amount,
                        'entry_type' => $entryType,
                        'ledger_id' => $ledgerId,
                        'is_deemed_positive' => isset($entry['ISDEEMEDPOSITIVE']) && $entry['ISDEEMEDPOSITIVE'] === 'Yes',
                    ];
                }
            } else {
                Log::error('Missing or invalid LEDGERNAME or AMOUNT in LEDGERENTRIES.LIST entry: ' . json_encode($entry));
            }
        }
        return array_values($ledgerEntries);
    }

    private function processAccountingAllocationForVoucher($voucherId, array $entries)
    {
        $voucherHeadIds = [];

        foreach ($entries as $entry) {
            $voucherHead = TallyVoucherHead::updateOrCreate([
                'voucher_id' => $voucherId,
                'amount' => $entry['amount'],
                'entry_type' => $entry['entry_type'],
                'ledger_id' => $entry['ledger_id'],
                'is_deemed_positive' => isset($entry['ISDEEMEDPOSITIVE']) && $entry['ISDEEMEDPOSITIVE'] === 'Yes',

            ]);

            $voucherHeadIds[] = [
                'voucher_head_id' => $voucherHead->voucher_head_id,
            ];
        }

        return $voucherHeadIds;
    }

    private function processInventoryEntries($inventoryEntries, array $voucherHeadIds, $companyId)
    {
        // Log::info('Processing Inventory Entries:', ['count' => is_array($inventoryEntries) ? count($inventoryEntries) : 1]);
        // Log::info('Available Voucher Head IDs:', ['voucherHeadIds' => $voucherHeadIds]);

        if (empty($voucherHeadIds)) {
            Log::error('No voucher_head_id available for inventory entry.');
            return;
        }

        $voucherHeadId = $voucherHeadIds[0];
        // Log::info('Using Voucher Head ID:', ['voucherHeadId' => $voucherHeadId]);

        $inventoryEntries = $this->tallyLicenseCheck->normalizeEntries($inventoryEntries);
        // Log::info('Normalized Inventory Entries:', ['entries' => $inventoryEntries]);

        $inventoryEntriesWithId = [];

        foreach ($inventoryEntries as $inventoryEntry) {
            $itemName = trim(htmlspecialchars_decode($inventoryEntry['STOCKITEMNAME'] ?? ''));

            if (!$itemName) {
                Log::warning('Skipped inventory entry due to missing STOCKITEMNAME:', ['entry' => $inventoryEntry]);
                continue;
            }

            $itemId = TallyItem::whereRaw('LOWER(item_name) = ?', [strtolower($itemName)])->where('company_id', $companyId)->value('item_id');

            if (!$itemId) {
                Log::error('Item ID not found for stock item:', [
                    'stock_item_name' => $itemName,
                    'attempted_match' => strtolower($itemName),
                    'inventoryEntry' => $inventoryEntry
                ]);
                continue;
            }

            $rateString = $inventoryEntry['RATE'] ?? null;
            $rate = null;
            $unit = null;
            if ($rateString) {
                $parts = explode('/', $rateString);
                if (count($parts) === 2) {
                    $rate = trim($parts[0]);
                    $unit = trim($parts[1]);
                } else {
                    Log::warning('Rate format not as expected:', ['stock_item_name' => $itemName, 'rate' => $rateString]);
                    $rate = $rateString;
                }
            }

            $unitId = null;
            if ($unit) {
                $unitId = TallyUnit::whereRaw('LOWER(unit_name) = ?', [strtolower($unit)])->where('company_id', $companyId)->value('unit_id');
                // Log::info('Extracted unit and unitId Data:', ['unit' => $unit, 'unitId' => $unitId]);
            }

            $billed_qty = $this->tallyLicenseCheck->extractNumericValue($inventoryEntry['BILLEDQTY'] ?? null);
            $actual_qty = $this->tallyLicenseCheck->extractNumericValue($inventoryEntry['ACTUALQTY'] ?? null);

            $igstRate = null;
            if (isset($inventoryEntry['RATEDETAILS.LIST'])) {
                foreach ($this->tallyLicenseCheck->ensureArray($inventoryEntry['RATEDETAILS.LIST']) as $rateDetail) {
                    if (isset($rateDetail['GSTRATEDUTYHEAD']) && $rateDetail['GSTRATEDUTYHEAD'] === 'IGST') {
                        $igstRate = $rateDetail['GSTRATE'] ?? null;
                        break;
                    }
                }
            }

            $inventoryData = [
                'voucher_head_id' => $voucherHeadId,
                'item_id' => $itemId,  // Ensure item_id is included in the data array
                'unit_id' => $unitId,
                'gst_taxability' => $inventoryEntry['GSTOVRDNTAXABILITY'] ?? null,
                'gst_source_type' => $inventoryEntry['GSTSOURCETYPE'] ?? null,
                'gst_item_source' => $inventoryEntry['GSTITEMSOURCE'] ?? null,
                'gst_ledger_source' => $inventoryEntry['GSTLEDGERSOURCE'] ?? null,
                'hsn_source_type' => $inventoryEntry['HSNSOURCETYPE'] ?? null,
                'hsn_item_source' => $inventoryEntry['HSNITEMSOURCE'] ?? null,
                'gst_rate_infer_applicability' => $inventoryEntry['GSTRATEINFERAPPLICABILITY'] ?? null,
                'gst_hsn_infer_applicability' => $inventoryEntry['GSTHSNINFERAPPLICABILITY'] ?? null,
                'rate' => $rate,
                'billed_qty' => $billed_qty ?? 0,
                'actual_qty' => $actual_qty ?? 0,
                'amount' => $inventoryEntry['AMOUNT'] ?? 0,
                'discount' => $inventoryEntry['DISCOUNT'] ?? 0,
                'igst_rate' => $igstRate,
                'gst_hsn_name' => $inventoryEntry['GSTHSNNAME'] ?? null,
            ];

            try {
                $tallyVoucherItem = TallyVoucherItem::create($inventoryData);

                $inventoryEntriesWithId[] = [
                    'voucher_item_id' => $tallyVoucherItem->voucher_item_id,
                    'stock_item_name' => $itemName,
                ];

                // Log::info('Inventory Entry Processed:', ['inventoryData' => $inventoryData]);

            } catch (\Exception $e) {
                Log::error('Error saving inventory entry:', [
                    'stock_item_name' => $itemName,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return $inventoryEntriesWithId;
    }

    private function processBatchAllocationsForVoucher(array $inventoryEntriesWithId, array $batchAllocations, $companyId)
    {
        $count = 0;

        foreach ($inventoryEntriesWithId as $inventoryEntries) {
            $stockItemName = $inventoryEntries['stock_item_name'];
            if (isset($batchAllocations[$stockItemName]) && is_array($batchAllocations[$stockItemName])) {
                foreach ($batchAllocations[$stockItemName] as $batch) {
                    if (isset($batch['BATCHNAME'], $batch['AMOUNT'])) {

                        $godownId = TallyGodown::select('tally_godowns.godown_id')
                            ->where('tally_godowns.godown_name', $batch['GODOWNNAME'] ?? null)
                            ->where('tally_godowns.company_id', $companyId)
                            ->value('tally_godowns.godown_id');


                        $billed_qty = $this->tallyLicenseCheck->extractNumericValue($inventoryEntry['BILLEDQTY'] ?? null);
                        $actual_qty = $this->tallyLicenseCheck->extractNumericValue($inventoryEntry['ACTUALQTY'] ?? null);
                        $amount = $this->tallyLicenseCheck->extractNumericValue($inventoryEntry['AMOUNT'] ?? null);

                        TallyBatchAllocation::updateOrCreate(
                            [
                                'voucher_item_id' => $inventoryEntries['voucher_item_id'],
                                'batch_name' => $batch['BATCHNAME'],
                                'destination_godown_name' => $batch['DESTINATIONGODOWNNAME'] ?? null,
                                'amount' => $amount,
                                'actual_qty' => $actual_qty,
                                'billed_qty' => $billed_qty,
                                'order_no' => $batch['ORDERNO'] ?? null,
                                'godown_id' => $godownId,
                            ]
                        );
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    private function processBillAllocationsForVoucher($voucherHeadIds, array $billAllocations)
    {
        // Log::info('Processing Bill Allocations', ['voucherHeadIds' => $voucherHeadIds, 'billAllocations' => $billAllocations]);

        foreach ($voucherHeadIds as $voucherHead) {
            // Log::info('Current voucher head being processed:', ['voucherHead' => $voucherHead]);

            foreach ($billAllocations as $ledgerName => $bills) {
                if (is_array($bills)) {
                    foreach ($bills as $bill) {
                        // Log::info('Current bill being processed:', ['bill' => $bill]);

                        if (is_array($bill)) {
                            try {
                                if (isset($bill['NAME'], $bill['AMOUNT'])) {
                                    TallyBillAllocation::updateOrCreate(
                                        [
                                            'voucher_head_id' => $voucherHead ?? null,  // Log voucher_head_id before using
                                            'name' => $bill['NAME'],
                                        ],
                                        [
                                            'bill_amount' => $bill['AMOUNT'],
                                            'year_end' => $bill['YEAREND'] ?? null,
                                            'bill_type' => $bill['BILLTYPE'] ?? null,
                                        ]
                                    );
                                    // Log::info('Successfully processed bill allocation', ['ledger_name' => $ledgerName, 'bill' => $bill]);
                                } else {
                                    Log::error('Missing NAME or AMOUNT in BILLALLOCATIONS.LIST entry: ' . json_encode($bill));
                                }
                            } catch (\Exception $e) {
                                Log::error('Error processing bill allocation: ' . $e->getMessage());
                            }
                        } else {
                            Log::error('Invalid bill format. Expected array but got ' . gettype($bill) . ': ' . json_encode($bill));
                        }
                    }
                } else {
                    Log::error('Invalid bill allocations format for ledger name: ' . $ledgerName . '. Expected array but got ' . gettype($bills));
                }
            }
        }
    }

    private function processBankAllocationsForVoucher($voucherHeadIds, array $bankAllocations)
    {
        // Log::info('Processing Bank Allocations', ['voucherHeadIds' => $voucherHeadIds, 'bankAllocations' => $bankAllocations]);

        foreach ($voucherHeadIds as $voucherHead) {
            // Log::info('Current voucher head being processed:', ['voucherHead' => $voucherHead]);

            $ledgerName = $voucherHead['ledger_name'] ?? null;
            // Log::info("Ledger name: " . $ledgerName);

            if (isset($bankAllocations[$ledgerName]) && is_array($bankAllocations[$ledgerName])) {
                foreach ($bankAllocations[$ledgerName] as $bank) {
                    // Log::info("Processing bank allocation: " . json_encode($bank));

                    try {
                        $allocation = TallyBankAllocation::updateOrCreate(
                            [
                                'voucher_head_id' => $voucherHead ?? null,
                            ],
                            [
                                'bank_date' => $this->tallyLicenseCheck->sanitizeDate($bank['DATE'] ?? null),
                                'instrument_date' => $this->tallyLicenseCheck->sanitizeDate($bank['INSTRUMENTDATE'] ?? null),
                                'instrument_number' => $bank['INSTRUMENTNUMBER'] ?? null,
                                'transaction_type' => $bank['TRANSACTIONTYPE'] ?? null,
                                'bank_name' => $bank['BANKNAME'] ?? null,
                                'amount' => $bank['AMOUNT'] ?? null,
                            ]
                        );
                        // Log::info("Bank allocation stored: " . json_encode($allocation));
                    } catch (\Exception $e) {
                        Log::error("Failed to process bank allocation: " . $e->getMessage());
                    }
                }
            } else {
                // Log::error("No bank allocations found for ledger: " . $ledgerName);
            }
        }
    }

}
