<?php

namespace App\DataTables\SuperAdmin;

use Carbon\Carbon;
use App\Models\TallyGroup;
use App\Models\TallyLedger;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use App\Facades\UtilityFacades;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class CustomerGroupDataTable extends DataTable
{

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->addColumn('name', function ($entry) {
                return '<a href="' . route('reports.CustomerGroupLedger', ['CustomerGroupLedger' => $entry->id]) . '">' . $entry->name . '</a>';
            })
            ->filterColumn('name', function($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->addColumn('total_sales', function ($entry) {
                
                $Data = $this->calculateCustomerAmount($entry);
                $totalAmount = $Data['totalAmount'];

                return number_format(abs($totalAmount), 2); 
            })
            ->addColumn('transaction', function ($entry) {
                
                $Data = $this->calculateCustomerAmount($entry);
                $transaction = $Data['transaction'];
                return number_format($transaction, 2);

            })
            ->addColumn('avg_sales', function ($entry) {
                
                $Data = $this->calculateCustomerAmount($entry);
                $transactionCount = $Data['salesCount'];
                $totalAmount = $Data['totalAmount'];

                $avgSales = $totalAmount / $transactionCount;

                return number_format(abs($avgSales), 2);

            })
            
            ->rawColumns(['name']);
    }


    private function calculateCustomerAmount($entry)
    {
        $salesAmt = TallyVoucherHead::whereHas('voucher', function ($query) use ($entry) {
            $query->join('tally_ledgers', 'tally_vouchers.ledger_guid', '=', 'tally_ledgers.guid')
                  ->where('tally_ledgers.parent', $entry->name)
                  ->where('tally_vouchers.voucher_type', 'sales');
        })->where('entry_type', 'debit')
        ->sum('amount');

        $creditAmt = TallyVoucherHead::whereHas('voucher', function ($query) use ($entry) {
            $query->join('tally_ledgers', 'tally_vouchers.ledger_guid', '=', 'tally_ledgers.guid')
                  ->where('tally_ledgers.parent', $entry->name)
                  ->where('tally_vouchers.voucher_type', 'credit note');
        })
        ->sum('amount');


        $Amt = $salesAmt + $creditAmt;

        $salesCount = TallyVoucher::join('tally_ledgers', 'tally_vouchers.ledger_guid', '=', 'tally_ledgers.guid')
        ->where('tally_ledgers.parent', $entry->name) // Adjust 'name' as needed based on your data
        ->where('tally_vouchers.voucher_type', 'sales')
        ->count();

        $creditCountCount = TallyVoucher::join('tally_ledgers', 'tally_vouchers.ledger_guid', '=', 'tally_ledgers.guid')
            ->where('tally_ledgers.parent', $entry->name) // Adjust 'name' as needed based on your data
            ->where('tally_vouchers.voucher_type', 'credit note')
            ->count();

        $transaction = $salesCount + $creditCountCount;

        return [
            'totalAmount' => $Amt,
            'transaction' => $transaction,
            'salesCount' => $salesCount
        ];
    }


    public function query(TallyGroup $model)
    {
        return $model->newQuery()->where('name', 'Sundry Debtors');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('daybook-table')
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
                searchInput.removeClass(\'form-control form-control-sm\').addClass(\'form-control ps-5 radius-30\').attr(\'placeholder\', \'Search by name\');
                searchInput.wrap(\'<div class="position-relative "></div>\');
                searchInput.parent().append(\'<span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>\');
                
                var select = $(table.api().table().container()).find(".dataTables_length select").removeClass(\'custom-select custom-select-sm form-control form-control-sm\').addClass(\'form-select form-select-sm\');
            }')
            ->parameters([
                "dom" =>  "
                               <'dataTable-top row'<'dataTable-dropdown page-dropdown col-lg-3 col-sm-12'l><'dataTable-botton table-btn col-lg-6 col-sm-12'B><'dataTable-search tb-search col-lg-3 col-sm-12'f>>
                             <'dataTable-container'<'col-sm-12'tr>>
                             <'dataTable-bottom row'<'col-sm-5'i><'col-sm-7'p>>
                               ",
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
            Column::make('name')->title(__('Name'))->searchable(),
            Column::make('parent')->title(__('Parent')),
            Column::make('total_sales')->title(__('Total Sales')),
            Column::make('transaction')->title(__('Transaction')),
            Column::make('avg_sales')->title(__('Avg Sales')),
        ];
    }

    protected function filename(): string
    {
        return 'Faq_' . date('YmdHis');
    }
}
