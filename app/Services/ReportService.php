<?php

namespace App\Services;
use App\Models\TallyVoucherItem;
use App\Models\TallyItem;
use App\Models\TallyCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ReportService
{
    public function companyData()
    {
        $companyId = session('selected_company_ids', []);
        \Log::info('Company ReportService ID Latest:', ['selected_company_ids' => $companyId]);

        if (empty($companyId)) {
            return [];
        }

        return TallyCompany::whereIn('company_id', $companyId)->pluck('company_id')->toArray();
    }


    public $normalizedNames = [
        'Direct Expenses, Expenses (Direct)' => 'Direct Expenses',
        'Direct Incomes, Income (Direct)' => 'Direct Incomes',
        'Indirect Expenses, Expenses (Indirect)' => 'Indirect Expenses',
        'Indirect Incomes, Income (Indirect)' => 'Indirect Incomes',
    ];

    public function formatAmt($amount) {
        if ($amount >= 1000) {
            $amount = $amount / 1000;
            return number_format($amount, 1) . 'k';
        } else {
            return number_format($amount, 2);
        }
    }

    public function extractNumericValue($value)
    {
        $numericValue = preg_replace('/[^\d.]/', '', $value);
        return (float) $numericValue;
    }

    public function formatNumber($value)
    {
        if (!is_numeric($value) || $value == 0) {
            return '-';
        }

        $floatValue = (float) $value;
        return number_format(abs($floatValue), 2, '.', '');
    }


    function getFinalQuery($query) {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        foreach ($bindings as $binding) {
            $escapedBinding = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
            $sql = preg_replace('/\?/', $escapedBinding, $sql, 1);
        }

        return $sql;
    }

    public function calculateStockItemVoucherBalance($stockItemName)
    {
        // Sum of billed quantities for 'Sales' vouchers
        // $stockItemVoucherSaleItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
        //     ->whereHas('tallyVoucher', function ($query) {
        //         $query->where('voucher_type', 'Sales');
        //     })->sum('billed_qty');

        $stockItemVoucherSaleItem = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type_id', 'Sales');
        })
        ->join('tally_items', 'tally_voucher_items.item_id', '=', 'tally_items.item_id') // Join to access item_name
        ->where('tally_items.item_name', $stockItemName) // Use correct column for stock item name
        ->sum('billed_qty'); // Sum billed quantities

        // Sum of billed quantities for 'Purchase' vouchers
        // $stockItemVoucherPurchaseItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
        //     ->whereHas('tallyVoucher', function ($query) {
        //         $query->where('voucher_type', 'Purchase');
        //     })->sum('billed_qty');

        $stockItemVoucherPurchaseItem = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type_id', 'Purchase');
        })
        ->join('tally_items', 'tally_voucher_items.item_id', '=', 'tally_items.item_id') // Join to access item_name
        ->where('tally_items.item_name', $stockItemName) // Use correct column for stock item name
        ->sum('billed_qty'); // Sum billed quantities

        // Sum of billed quantities for 'Credit Note' vouchers
        // $stockItemVoucherCreditNoteItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
        //     ->whereHas('tallyVoucher', function ($query) {
        //         $query->where('voucher_type', 'Credit Note');
        //     })->sum('billed_qty');

        $stockItemVoucherCreditNoteItem = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type_id', 'Credit Note');
        })
        ->join('tally_items', 'tally_voucher_items.item_id', '=', 'tally_items.item_id') // Join to access item_name
        ->where('tally_items.item_name', $stockItemName) // Use correct column for stock item name
        ->sum('billed_qty'); // Sum billed quantities

        // Sum of billed quantities for 'Debit Note' vouchers
        // $stockItemVoucherDebitNoteItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
        //     ->whereHas('tallyVoucher', function ($query) {
        //         $query->where('voucher_type', 'Debit Note');
        //     })->sum('billed_qty');

        $stockItemVoucherDebitNoteItem = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type_id', 'Debit Note');
        })
        ->join('tally_items', 'tally_voucher_items.item_id', '=', 'tally_items.item_id') // Join to access item_name
        ->where('tally_items.item_name', $stockItemName) // Use correct column for stock item name
        ->sum('billed_qty'); // Sum billed quantities

        // Calculate total stock item voucher balance
        $stockItemVoucherBalance = ($stockItemVoucherSaleItem - $stockItemVoucherCreditNoteItem) - ($stockItemVoucherPurchaseItem - $stockItemVoucherDebitNoteItem);

        // Optionally, you can return the purchase item billed_qty or use it elsewhere
        return [
            'balance' => $stockItemVoucherBalance,
            'purchase_qty' => $stockItemVoucherPurchaseItem,
            'debit_note_qty' => $stockItemVoucherDebitNoteItem
        ];
    }

    public function calculateStockItemVoucherAmount($stockItemName)
    {
        // Sum of billed quantities for 'Sales' vouchers
        $stockItemVoucherSaleAmount = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type_id', 'Sales');
        })
        ->join('tally_items', 'tally_voucher_items.item_id', '=', 'tally_items.item_id') // Join to access item_name
        ->where('tally_items.item_name', $stockItemName) // Use correct column for stock item name
        ->sum('amount'); // Sum billed quantities

        // Sum of billed quantities for 'Purchase' vouchers
        // $purchaseVoucherData = TallyVoucherItem::where('stock_item_name', $stockItemName)
        //     ->whereHas('tallyVoucher', function ($query) {
        //         $query->where('voucher_type', 'Purchase');
        //     })
        //     ->selectRaw('SUM(amount) as total_amount, MIN(tally_vouchers.voucher_date) as voucher_date')
        //     ->join('tally_vouchers', 'tally_voucher_items.voucher_head_id', '=', 'tally_vouchers.id')
        //     ->first();

        $purchaseVoucherData = TallyVoucherItem::join('tally_items', 'tally_voucher_items.item_id', '=', 'tally_items.item_id') // Join to access item_name
            ->join('tally_vouchers', 'tally_voucher_items.voucher_head_id', '=', 'tally_vouchers.voucher_id') // Join to access voucher details
            ->where('tally_items.item_name', $stockItemName) // Filter by stock item name
            ->where('tally_vouchers.voucher_type_id', 'Purchase') // Filter for 'Purchase' vouchers
            ->selectRaw('SUM(tally_voucher_items.amount) as total_amount, MIN(tally_vouchers.voucher_date) as voucher_date') // Aggregate data
            ->first();


        // Sum of billed quantities for 'Credit Note' vouchers
        // $stockItemVoucherCreditNoteAmount = TallyVoucherItem::where('stock_item_name', $stockItemName)
        //     ->whereHas('tallyVoucher', function ($query) {
        //         $query->where('voucher_type', 'Credit Note');
        //     })->sum('amount');
        $stockItemVoucherCreditNoteAmount = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type_id', 'Credit Note');
        })
        ->join('tally_items', 'tally_voucher_items.item_id', '=', 'tally_items.item_id') // Join to access item_name
        ->where('tally_items.item_name', $stockItemName) // Use correct column for stock item name
        ->sum('amount'); // Sum billed quantities

        // Sum of billed quantities for 'Debit Note' vouchers
        // $debitNoteVoucherData = TallyVoucherItem::where('stock_item_name', $stockItemName)
        //     ->whereHas('tallyVoucher', function ($query) {
        //         $query->where('voucher_type', 'Debit Note');
        //     })
        //     ->selectRaw('SUM(amount) as total_amount, MIN(tally_vouchers.voucher_date) as voucher_date')
        //     ->join('tally_vouchers', 'tally_voucher_items.voucher_head_id', '=', 'tally_vouchers.id')
        //     ->first();

            $debitNoteVoucherData = TallyVoucherItem::join('tally_items', 'tally_voucher_items.item_id', '=', 'tally_items.item_id') // Join to access the stock item name
        ->join('tally_vouchers', 'tally_voucher_items.voucher_head_id', '=', 'tally_vouchers.voucher_id') // Join to access voucher details
        ->where('tally_items.item_name', $stockItemName) // Filter by the stock item name
        ->where('tally_vouchers.voucher_type_id', 'Debit Note') // Filter for 'Debit Note' vouchers
        ->selectRaw('SUM(tally_voucher_items.amount) as total_amount, MIN(tally_vouchers.voucher_date) as voucher_date') // Aggregate data
        ->first();

        return [
            'purchase_amt' => $purchaseVoucherData->total_amount ?? 0,
            'purchase_date' => $purchaseVoucherData->voucher_date ?? null,
            'debit_note_amt' => $debitNoteVoucherData->total_amount ?? 0,
            'debit_note_date' => $debitNoteVoucherData->voucher_date ?? null,
        ];
    }

    public function calculateStockValue()
    {
        $companyIds = $this->companyData();

        $tallyItems = TallyItem::whereIn('company_id', $companyIds)->get();
        $stock_value = 0;

        foreach ($tallyItems as $entry) {
            $openingBalance = $this->extractNumericValue($entry->opening_balance);
            $openingValue = $this->extractNumericValue($entry->opening_value);

            $stockItemData = $this->calculateStockItemVoucherBalance($entry->name);
            $stockItemVoucherPurchaseBalance = $stockItemData['purchase_qty'];
            $stockItemVoucherDebitNoteBalance = $stockItemData['debit_note_qty'];
            $stockItemVoucherHandBalance = $stockItemData['balance'];

            $stockAmountData = $this->calculateStockItemVoucherAmount($entry->name);
            $stockItemVoucherPurchaseAmount = $stockAmountData['purchase_amt'];
            $stockItemVoucherDebitNoteAmount = $stockAmountData['debit_note_amt'];

            $openingAmount = ($stockItemVoucherPurchaseAmount + $stockItemVoucherDebitNoteAmount);

            $finalOpeningValue = $openingValue - $openingAmount;
            // $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance;
            $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance;

            if ($finalOpeningBalance != 0) {
                $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
                $stockItemVoucherSaleValue = number_format($stockItemVoucherSaleValue, 4, '.', '');
                $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
                $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;
                $stock_value += $stockOnHandValue;
            }
        }
        return number_format($stock_value, 3);
    }
}
