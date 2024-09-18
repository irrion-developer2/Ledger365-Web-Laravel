<?php

namespace App\DataTables\SuperAdmin;

use Carbon\Carbon;
use App\Models\TallyItem;
use App\Models\TallyVoucherItem;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StockItemDataTable extends DataTable
{

    private function extractNumericValue($value)
    {
        // Remove any characters that are not digits, commas, or periods
        $numericPart = preg_replace('/[^\d.,]/', '', $value);
        
        // Replace commas with dots for consistency, and then remove any remaining dots that are not decimal points
        $numericPart = str_replace(',', '', $numericPart);
        
        // Convert to float
        return (float) $numericPart;
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->addColumn('name', function ($entry) {
                return '<a href="' . route('StockItem.items', ['StockItem' => $entry->id]) . '">' . $entry->name . '</a>';
            })
            ->filterColumn('name', function($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->addColumn('stock_on_hand_opening_balance', function ($entry) {
                $openingBalance = trim($entry->opening_balance);

                $numericPart = '';
                $unitPart = '';
                
                if (preg_match('/^([\d.,]+)\s*(.*)$/', $openingBalance, $matches)) {
                    $numericPart = $matches[1];
                    $unitPart = isset($matches[2]) ? $matches[2] : '';
                } else {
                    // \Log::warning("Failed to match opening balance: $openingBalance");
                }
                $openingBalanceValue = (float) str_replace([',', ' '], '', $numericPart);

                $unit = $entry->unit ?? $entry->pluck('unit')->filter()->first();

                $stockItemData = $this->calculateStockItemVoucherBalance($entry->name);
                $stockItemVoucherBalance = $stockItemData['balance'];
                // $finalOpeningBalance = $openingBalanceValue + $stockItemVoucherBalance;
                // dd($finalOpeningBalance);

                $stockOnHandBalance = $openingBalanceValue - $stockItemVoucherBalance;

                return $stockOnHandBalance . ' ' . $unit;
            })

            ->addColumn('stock_on_hand_opening_value', function ($entry) {

                // Initialize variables
                $stockOnHandBalance = 0;
                $openingBalance = 0;
                $stockOnHandValue = 0;
            
                // Extract numeric values from opening balance and opening value
                $openingBalance = $this->extractNumericValue($entry->opening_balance);
                $openingValue = $this->extractNumericValue($entry->opening_value);
            
                // Retrieve stock item data for calculations
                $stockItemData = $this->calculateStockItemVoucherBalance($entry->name);
                $stockItemVoucherPurchaseBalance = $stockItemData['purchase_qty'];
                $stockItemVoucherHandBalance = $stockItemData['balance'];
            
                $stockAmountData = $this->calculateStockItemVoucherAmount($entry->name);
                $stockItemVoucherAmount = $stockAmountData['purchase_amt'];
            
                // Calculate final opening values and balances
                $finalOpeningValue = $openingValue - $stockItemVoucherAmount;
                $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance;
            
                // Calculate stock on hand value
                if ($openingBalance == 0) {
                    $stockItemVoucherSaleValue = 0;
                    $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
                } else {
                    $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
                    $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
                }
            
                // Calculate stock on hand value
                $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;
            
                // Return the calculated stock on hand value
                return number_format($stockOnHandValue, 2);
            })
            ->editColumn('opening_value', function ($entry) {
                $openingValue = is_numeric($entry->opening_value) ? (float)$entry->opening_value : 0;
                return abs($openingValue);
            })
        
            ->rawColumns(['name'])
            ->orderColumn('stock_on_hand_opening_balance', function($query, $order) {
                // Custom sorting logic for `stock_on_hand_opening_balance`
                $direction = $order === 'desc' ? 'desc' : 'asc';
                $query->orderByRaw("CAST(SUBSTRING_INDEX(opening_balance, ' ', 1) AS DECIMAL) {$direction}");
            })
            ->orderColumn('stock_on_hand_opening_value', function($query, $order) {
                // Custom sorting logic for `stock_on_hand_opening_value`
                $direction = $order === 'desc' ? 'desc' : 'asc';
                $query->orderByRaw("CAST(SUBSTRING_INDEX(opening_value, ' ', 1) AS DECIMAL) {$direction}");
            });
    }

    public function query(TallyItem $model)
    {
        return $model->newQuery()->with('tallyVoucherItems');
    }


    
    private function calculateStockItemVoucherAmount($stockItemName)
    {
        // Sum of billed quantities for 'Sales' vouchers
        $stockItemVoucherSaleAmount = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Sales');
            })->sum('amount');

        // Sum of billed quantities for 'Purchase' vouchers
        $stockItemVoucherPurchaseAmount = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Purchase');
            })->sum('amount');

        // Sum of billed quantities for 'Credit Note' vouchers
        $stockItemVoucherCreditNoteAmount = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Credit Note');
            })->sum('amount');

        // Sum of billed quantities for 'Debit Note' vouchers
        $stockItemVoucherDebitNoteAmount = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Debit Note');
            })->sum('amount');

        return [
            'purchase_amt' => $stockItemVoucherPurchaseAmount
        ];
    }

    private function calculateStockItemVoucherBalance($stockItemName)
    {
        // Sum of billed quantities for 'Sales' vouchers
        $stockItemVoucherSaleItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Sales');
            })->sum('billed_qty');

        // Sum of billed quantities for 'Purchase' vouchers
        $stockItemVoucherPurchaseItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Purchase');
            })->sum('billed_qty');

        // Sum of billed quantities for 'Credit Note' vouchers
        $stockItemVoucherCreditNoteItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Credit Note');
            })->sum('billed_qty');

        // Sum of billed quantities for 'Debit Note' vouchers
        $stockItemVoucherDebitNoteItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Debit Note');
            })->sum('billed_qty');

        // Calculate total stock item voucher balance
        $stockItemVoucherBalance = ($stockItemVoucherSaleItem - $stockItemVoucherCreditNoteItem) - ($stockItemVoucherPurchaseItem - $stockItemVoucherDebitNoteItem);

        // Optionally, you can return the purchase item billed_qty or use it elsewhere
        return [
            'balance' => $stockItemVoucherBalance,
            'purchase_qty' => $stockItemVoucherPurchaseItem
        ];
    }


    public function html()
    {
        return $this->builder()
            ->setTableId('stock-item-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0)
            ->language([
                "paginate" => [
                    "next" => '<i class="ti ti-chevron-right"></i>next',
                    "previous" => '<i class="ti ti-chevron-left"></i>Prev'
                ],
                'lengthMenu' => __('Show _MENU_ entries'),
                "searchPlaceholder" => __('Search...'), "search" => ""
            ])
            ->initComplete('function() {
                var table = this;
                var searchInput = $(\'#\'+table.api().table().container().id+\' label input[type="search"]\');
                searchInput.removeClass(\'form-control form-control-sm\').addClass(\'form-control ps-5 radius-30\').attr(\'placeholder\', \'Search...\');
                searchInput.wrap(\'<div class="position-relative"></div>\');
                searchInput.parent().append(\'<span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>\');
                
                var select = $(table.api().table().container()).find(".dataTables_length select").removeClass(\'custom-select custom-select-sm form-control form-control-sm\').addClass(\'form-select form-select-sm\');
            }')
            ->parameters([
                "dom" =>  "
                               <'dataTable-top row'<'dataTable-dropdown page-dropdown col-lg-3 col-sm-12'l><'dataTable-botton table-btn col-lg-6 col-sm-12'B><'dataTable-search tb-search col-lg-3 col-sm-12'f>>
                             <'dataTable-container'<'col-sm-12'tr>>
                             <'dataTable-bottom row'<'col-sm-5'i><'col-sm-7'p>>
                               ",
                // 'paging' => false,
                'buttons'   => [
                ],
                "scrollX" => true,
                "drawCallback" => 'function( settings ) {
                    var tooltipTriggerList = [].slice.call(
                        document.querySelectorAll("[data-bs-toggle=tooltip]")
                      );
                      var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                      });
                      var popoverTriggerList = [].slice.call(
                        document.querySelectorAll("[data-bs-toggle=popover]")
                      );
                      var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                        return new bootstrap.Popover(tooltipTriggerEl);
                      });
                      var toastElList = [].slice.call(document.querySelectorAll(".toast"));
                      var toastList = toastElList.map(function (toastEl) {
                        return new bootstrap.Toast(toastEl);
                      });
                }'
            ])->language([
                'buttons' => [
                    'create' => __('Create'),
                    'export' => __('Export'),
                    'print' => __('Print'),
                    'reset' => __('Reset'),
                    'reload' => __('Reload'),
                    'excel' => __('Excel'),
                    'csv' => __('CSV'),
                ]
            ]);
    }

    protected function getColumns()
    {
        return [
            // Column::make('No')->data('DT_RowIndex')->name('DT_RowIndex')->searchable(false)->orderable(false),
            Column::make('name')->title(__('Name'))->searchable(true)->addClass('fixed-column'),
            Column::make('parent')->title(__('Stock Group')),
            Column::make('opening_balance')->title(__('Stock (qty)'))->addClass('text-end'),
            Column::make('opening_value')->title(__('Stock (value)'))->addClass('text-end'),
            Column::make('stock_on_hand_opening_balance')->title(__('Stock On Hand (qty)'))->addClass('text-end'),
            Column::make('stock_on_hand_opening_value')->title(__('Stock On Hand (value)'))->addClass('text-end'),
            // Column::make('parent')->title(__('Total Sales Qty')),
            // Column::make('parent')->title(__('GST Total Sales Value')),
            // Column::make('parent')->title(__('&#8377 Last Sale (value)')),
            // Column::make('parent')->title(__('Last Sale (Date)')),
            // Column::make('parent')->title(__('GST Rate')),
            Column::make('category')->title(__('Stock Category')),
            Column::make('alias')->title(__('Alias')),
            // Column::make('parent')->title(__('Supplier Item No.')),
        ];
    }

    protected function filename(): string
    {
        return 'StockItem_' . date('YmdHis');
    }
}
