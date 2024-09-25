<?php

namespace App\DataTables\SuperAdmin;

use Carbon\Carbon;
use App\Models\TallyVoucher;
use App\Models\TallyCompany;
use App\Facades\UtilityFacades;
use Yajra\DataTables\Html\Column;
use App\Services\ReportService;
use Yajra\DataTables\Services\DataTable;

class PaymentRegisterDataTable extends DataTable
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->addColumn('entry_type', function ($entry) {
                return $entry->entry_type; // Directly from the joined table
            })
            ->addColumn('credit', function ($entry) {
                // Return the credit amount if the entry type is credit
                return $entry->entry_type === 'credit' ? number_format(abs($entry->amount), 2, '.', '') : '-';
            })
            ->addColumn('debit', function ($entry) {
                // Return the debit amount if the entry type is debit
                return $entry->entry_type === 'debit' ? number_format(abs($entry->amount), 2, '.', '') : '-';
            })
            ->addColumn('voucher_number', function ($entry) {
                return '<a href="' . route('reports.VoucherItemPayment', ['VoucherItem' => $entry->id]) . '">' . $entry->voucher_number . '</a>';
            })
            ->rawColumns(['voucher_number']);
    }

    public function query(TallyVoucher $model)
    {
        $companyGuids = $this->reportService->companyData();

        $query = $model->newQuery()
            ->select('tally_vouchers.*', 'tally_voucher_heads.entry_type', 'tally_voucher_heads.amount')
            ->leftJoin('tally_voucher_heads', function($join) {
                $join->on('tally_vouchers.party_ledger_name', '=', 'tally_voucher_heads.ledger_name')
                    ->on('tally_vouchers.id', '=', 'tally_voucher_heads.tally_voucher_id'); // Adjust as needed
            })
            ->where('tally_vouchers.voucher_type', 'Payment')
            ->whereIn('company_guid', $companyGuids);


            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');

            $customDateRange = request()->get('custom_date_range');

            // Handle custom date ranges
            if ($customDateRange) {
                switch ($customDateRange) {
                    case 'this_month':
                        $startDate = now()->startOfMonth()->toDateString();
                        $endDate = now()->endOfMonth()->toDateString();
                        break;
                    case 'last_month':
                        $startDate = now()->subMonth()->startOfMonth()->toDateString();
                        $endDate = now()->subMonth()->endOfMonth()->toDateString();
                        break;
                    case 'this_quarter':
                        $startDate = now()->firstOfQuarter()->toDateString();
                        $endDate = now()->lastOfQuarter()->toDateString();
                        break;
                    case 'prev_quarter':
                        $startDate = now()->subQuarter()->firstOfQuarter()->toDateString();
                        $endDate = now()->subQuarter()->lastOfQuarter()->toDateString();
                        break;
                    case 'this_year':
                        $startDate = now()->startOfYear()->toDateString();
                        $endDate = now()->endOfYear()->toDateString();
                        break;
                    case 'prev_year':
                        $startDate = now()->subYear()->startOfYear()->toDateString();
                        $endDate = now()->subYear()->endOfYear()->toDateString();
                        break;
                }
            }


            if ($startDate && $endDate) {
                try {
                    $startDate = Carbon::parse($startDate)->startOfDay();
                    $endDate = Carbon::parse($endDate)->endOfDay();
                    $query->whereBetween('voucher_date', [$startDate, $endDate]);
                } catch (\Exception $e) {
                    \Log::error('Date parsing error: ' . $e->getMessage());
                }
            }

        return $query;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('payment-register-table')
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
                searchInput.removeClass(\'form-control form-control-sm\').addClass(\'form-control ps-5 radius-30\').attr(\'placeholder\', \'Search Order\');
                searchInput.wrap(\'<div class="position-relative pt-1"></div>\');
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
            Column::make('voucher_date')->title(__('Date')),
            Column::make('party_ledger_name')->title(__('name')),
            Column::make('voucher_number')->title(__('Transaction')),
            Column::make('voucher_type')->title(__('Transaction Type')),
            Column::make('debit')->title(__('Debit')),
            Column::make('credit')->title(__('Credit')),
        ];
    }

    protected function filename(): string
    {
        return 'Faq_' . date('YmdHis');
    }
}
