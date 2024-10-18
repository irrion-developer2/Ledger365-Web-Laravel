<?php

namespace App\DataTables\SuperAdmin;

use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Database\Eloquent\Builder;

class CustomerDataTable extends DataTable
{
    
    protected $topCustomerCount;
    protected $noSalesCount;

    public function __construct()
    {
        $this->topCustomerCount = TallyLedger::where('parent', 'Sundry Debtors')
            ->whereNotNull('opening_balance')
            ->where('opening_balance', '!=', 0)
            ->count();

        $this->noSalesCount = TallyLedger::where('parent', 'Sundry Debtors')
            ->where(function ($query) {
                $query->where('opening_balance', '=', 0)
                    ->orWhereNull('opening_balance');
            })
            ->count();



    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->editColumn('name', function ($data) {
                $url = route('customers.show', ['customer' => $data->guid]);
                return '<a href="' . $url . '" style="color: #337ab7;">' . $data->name . '</a>';
            })
            ->editColumn('bill_credit_period', function ($data) {
                if (empty($data->bill_credit_period)) {
                    return '-';
                }
                return preg_replace('/\D/', '', $data->bill_credit_period);
            })
            ->editColumn('credit_limit', function ($data) {
                if (empty($data->credit_limit)) {
                    return '-';
                }
                return preg_replace('/\D/', '', $data->credit_limit);
            })
            ->addColumn('sales', function ($data) {
                $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Sales')
                    ->pluck('ledger_guid');

                $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                    ->sum('amount');

                return number_format(abs($totalSales), 2);
            })
            ->addColumn('sales_last_30_days', function ($data) {
                $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Sales')
                    ->where('voucher_date', '>=', Carbon::now()->subDays(30)->startOfDay())
                    ->where('voucher_date', '<=', Carbon::now()->endOfDay())
                    ->pluck('ledger_guid');
    
                $totalSalesLast30Days = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                    ->sum('amount');
    
                return number_format(abs($totalSalesLast30Days), 2);
            })

            ->addColumn('outstanding', function ($data) {
                $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Sales')
                    ->pluck('ledger_guid');

                $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                    ->sum('amount');

                return number_format($totalSales, 2);
            })

            ->addColumn('overdue', function ($data) {
                $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Sales')
                    ->pluck('ledger_guid');

                $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                    ->sum('amount');

                return number_format($totalSales, 2);
            })
            ->addColumn('payment_collection', function ($data) {

                $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                ->where('voucher_type', 'Receipt')
                ->pluck('id', 'ledger_guid');

                $ledgerGuids = $ledgerData->keys();
                $tallyVoucherIds = $ledgerData->values();

                $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                ->whereIn('tally_voucher_id', $tallyVoucherIds)
                ->sum('amount');

                return number_format($totalSales, 2);
            })
            ->addColumn('payment_date', function ($data) {

                $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Receipt')
                    ->pluck('id', 'ledger_guid');
            
                $ledgerGuids = $ledgerData->keys();
                $tallyVoucherIds = $ledgerData->values();

                $latestReceipt = TallyVoucher::whereIn('ledger_guid', $ledgerGuids)
                    ->whereIn('id', $tallyVoucherIds)
                    ->orderBy('voucher_date', 'desc') 
                    ->first();
            
                if ($latestReceipt) {
                    return \Carbon\Carbon::parse($latestReceipt->voucher_date)->format('j F Y');
                } else {
                    return '-'; 
                }
            })
            
            
            ->rawColumns(['name']);
    }

    public function query(TallyLedger $model)
    {
        $filter = request()->get('filter', 'all');

        if ($filter === 'top_customers') {
            return $model->newQuery()->where('parent', 'Sundry Debtors')
                ->whereNotNull('opening_balance')
                ->where('opening_balance', '!=', 0);
        } elseif ($filter === 'no_sales') {
            return $model->newQuery()->where('parent', 'Sundry Debtors')
                ->where(function ($query) {
                    $query->where('opening_balance', '=', 0)
                        ->orWhereNull('opening_balance');
                });
        }

        return $model->newQuery()->where('parent', 'Sundry Debtors');
    }
    public function html()
    {
        return $this->builder()
            ->setTableId('customer-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
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
                searchInput.wrap(\'<div class="position-relative "></div>\');
                searchInput.parent().append(\'<span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>\');
                
                var select = $(table.api().table().container()).find(".dataTables_length select").removeClass(\'custom-select custom-select-sm form-control form-control-sm\').addClass(\'form-select form-select-sm\');
            }')
            ->parameters([
                "dom" =>  "
                               <'dataTable-top row'<'dataTable-dropdown page-dropdown col-lg-3 col-sm-12'l><'dataTable-buttons col-lg-6 col-sm-12'B><'dataTable-search tb-search col-lg-3 col-sm-12'f>>
                             <'dataTable-container'<'col-sm-12'tr>>
                             <'dataTable-bottom row'<'col-sm-5'i><'col-sm-7'p>>
                               ",
                'buttons' => [
                ],
                "scrollX" => true,
                // "paging" => false,
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
    


    // public function html()
    // {
    //     return $this->builder()
    //         ->setTableId('customer-table')
    //         ->columns($this->getColumns())
    //         ->minifiedAjax()
    //         ->orderBy(0, 'asc')
    //         ->language([
    //             "paginate" => [
    //                 "next" => '<i class="ti ti-chevron-right"></i>next',
    //                 "previous" => '<i class="ti ti-chevron-left"></i>Prev'
    //             ],
    //             'lengthMenu' => __('Show _MENU_ entries'),
    //             "searchPlaceholder" => __('Search...'), "search" => ""
    //         ])
    //         ->initComplete('function() {
    //             var table = this;
    //             var searchInput = $(\'#\'+table.api().table().container().id+\' label input[type="search"]\');
    //             searchInput.removeClass(\'form-control form-control-sm\').addClass(\'form-control ps-5 radius-30\').attr(\'placeholder\', \'Search...\');
    //             searchInput.wrap(\'<div class="position-relative "></div>\');
    //             searchInput.parent().append(\'<span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>\');
                
    //             var select = $(table.api().table().container()).find(".dataTables_length select").removeClass(\'custom-select custom-select-sm form-control form-control-sm\').addClass(\'form-select form-select-sm\');
    //         }')
    //         ->parameters([
    //             "dom" =>  "
    //                            <'dataTable-top row'<'dataTable-dropdown page-dropdown col-lg-3 col-sm-12'l><'dataTable-buttons col-lg-6 col-sm-12'B><'dataTable-search tb-search col-lg-3 col-sm-12'f>>
    //                          <'dataTable-container'<'col-sm-12'tr>>
    //                          <'dataTable-bottom row'<'col-sm-5'i><'col-sm-7'p>>
    //                            ",
    //             'buttons' => [
    //             ],
    //             "scrollX" => true,
    //             "paging" => false,
    //             "drawCallback" => 'function( settings ) {
    //                 var tooltipTriggerList = [].slice.call(
    //                     document.querySelectorAll("[data-bs-toggle=tooltip]")
    //                   );
    //                   var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    //                     return new bootstrap.Tooltip(tooltipTriggerEl);
    //                   });
    //                   var popoverTriggerList = [].slice.call(
    //                     document.querySelectorAll("[data-bs-toggle=popover]")
    //                   );
    //                   var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    //                     return new bootstrap.Popover(tooltipTriggerEl);
    //                   });
    //                   var toastElList = [].slice.call(document.querySelectorAll(".toast"));
    //                   var toastList = toastElList.map(function (toastEl) {
    //                     return new bootstrap.Toast(toastEl);
    //                   });
    //             }'
    //         ])->language([
    //             'buttons' => [
    //                 'create' => __('Create'),
    //                 'export' => __('Export'),
    //                 'print' => __('Print'),
    //                 'reset' => __('Reset'),
    //                 'reload' => __('Reload'),
    //                 'excel' => __('Excel'),
    //                 'csv' => __('CSV'),
    //             ]
    //         ]);
    // }

    protected function getColumns()
    {
        return [
            // Column::make('No')->data('DT_RowIndex')->name('DT_RowIndex')->searchable(false)->orderable(false),
            Column::make('name')->title(__('Name'))->addClass('fixed-column'),
            Column::make('parent')->title(__('Group')),
            Column::make('sales')->title(__('Sales'))->addClass('text-end'),
            Column::make('sales_last_30_days')->title(__('Sales (Last 30 days)'))->addClass('text-end'),
            Column::make('outstanding')->title(__('₹ Net Outstanding'))->addClass('text-end'),
            Column::make('overdue')->title(__('₹ Overdue'))->addClass('text-end'),
            Column::make('payment_collection')->title(__('₹ Pmt Collection (CFY)'))->addClass('text-end'),
            Column::make('payment_date')->title(__('Last Payment (Date)'))->addClass('text-end'),
            Column::make('phone_no')->title(__('Phone. No.')),
            Column::make('email')->title(__('Email')),
            Column::make('credit_limit')->title(__('₹ Credit Limit'))->addClass('text-end'),
            Column::make('bill_credit_period')->title(__('₹ Credit Period'))->addClass('text-end'),
        ];
    }

    protected function filename(): string
    {
        return 'Customer_' . date('YmdHis');
    }
}
