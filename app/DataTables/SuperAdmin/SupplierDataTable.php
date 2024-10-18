<?php

namespace App\DataTables\SuperAdmin;

use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use App\Facades\UtilityFacades;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SupplierDataTable extends DataTable
{

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->editColumn('opening_balance', function ($data) {
                return $data->opening_balance ? number_format(abs($data->opening_balance), 2) : '0.00';
            })
            ->addColumn('return30', function ($data) {

                $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                ->where('voucher_type', 'Debit Note')
                ->where('voucher_date', '>=', Carbon::now()->subDays(30)->startOfDay())
                ->where('voucher_date', '<=', Carbon::now()->endOfDay())
                ->pluck('id', 'ledger_guid');

                $ledgerGuids = $ledgerData->keys();
                $tallyVoucherIds = $ledgerData->values();

                $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                ->whereIn('tally_voucher_id', $tallyVoucherIds)
                ->sum('amount');

                return number_format(abs($totalSales), 2);

            })
            ->addColumn('purchase', function ($data) {
                $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                ->where('voucher_type', 'Purchase')
                ->pluck('id', 'ledger_guid');

                $ledgerGuids = $ledgerData->keys();
                $tallyVoucherIds = $ledgerData->values();

                $totalPurchase = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                ->whereIn('tally_voucher_id', $tallyVoucherIds)
                ->sum('amount');

                return number_format(abs($totalPurchase), 2);
            })
            ->addColumn('purchase_last_30_days', function ($data) {
                $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                ->where('voucher_type', 'Purchase')
                ->where('voucher_date', '>=', Carbon::now()->subDays(30)->startOfDay())
                ->where('voucher_date', '<=', Carbon::now()->endOfDay())
                ->pluck('id', 'ledger_guid');

                $ledgerGuids = $ledgerData->keys();
                $tallyVoucherIds = $ledgerData->values();

                $totalPurchase = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                ->whereIn('tally_voucher_id', $tallyVoucherIds)
                ->sum('amount');

                return number_format(abs($totalPurchase), 2);
            })
            ->addColumn('outstanding', function ($data) {
                $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Purchase')
                    ->pluck('ledger_guid');

                $totalPurchase = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                    ->sum('amount');

                return number_format($totalPurchase, 2);
            })
            ->addColumn('overdue', function ($data) {
                $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Purchase')
                    ->pluck('ledger_guid');

                $totalPurchase = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                    ->sum('amount');

                return number_format($totalPurchase, 2);
            })
            ->addColumn('purchase_collection', function ($data) {

                $ledgerPurchaseData = TallyVoucher::where('ledger_guid', $data->guid)
                ->where('voucher_type', 'Purchase')
                ->pluck('id', 'ledger_guid');

                $ledgerPurchaseGuids = $ledgerPurchaseData->keys();
                $tallyPurchaseVoucherIds = $ledgerPurchaseData->values();

                $totalPurchase = TallyVoucherHead::whereIn('ledger_guid', $ledgerPurchaseGuids)
                ->whereIn('tally_voucher_id', $tallyPurchaseVoucherIds)
                ->sum('amount');


                $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                ->where('voucher_type', 'Debit Note')
                ->pluck('id', 'ledger_guid');
                $ledgerGuids = $ledgerData->keys();
                $tallyVoucherIds = $ledgerData->values();
                $totalDebit = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                ->whereIn('tally_voucher_id', $tallyVoucherIds)
                ->sum('amount');

                $pmtCollection = $totalPurchase + $totalDebit;


                return number_format($pmtCollection, 2);
            })
            ->addColumn('payment_collection', function ($data) {

                $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                ->where('voucher_type', 'Payment')
                ->pluck('id', 'ledger_guid');

                $ledgerGuids = $ledgerData->keys();
                $tallyVoucherIds = $ledgerData->values();

                $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                ->whereIn('tally_voucher_id', $tallyVoucherIds)
                ->sum('amount');

                return number_format(abs($totalSales), 2);

            })
            ->addColumn('overdue_date', function ($data) {

                $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Purchase')
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
            })->addColumn('voucher_date', function ($data) {

                $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Payment')
                    ->pluck('id', 'ledger_guid');
                    // ->get();
                    // dd($ledgerData);

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
            ->editColumn('name', function ($data) {
                $url = route('customers.show', ['customer' => $data->guid]);
                return '<a href="' . $url . '" style="color: #337ab7;">' . $data->name . '</a>';
            })
            ->rawColumns(['name']);
    }

    public function query(TallyLedger $model)
    {
        return $model->newQuery()->where('parent', 'Sundry Creditors');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('supplier-table')
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
            // Column::make('No')->data('DT_RowIndex')->name('DT_RowIndex')->searchable(false)->orderable(false),
            // Column::make('guid')->title(__('Guid')),
            // Column::make('guid')->title(__('ID'))->addClass('fixed-column'),
            Column::make('name')->title(__('Name'))->addClass('fixed-column'),
            Column::make('parent')->title(__('Group')),
            Column::make('purchase')->title(__('₹ Purchase'))->addClass('text-end'),
            Column::make('purchase_last_30_days')->title(__('₹ Purchase (Last 30 days)'))->addClass('text-end'),
            Column::make('return30')->title(__('₹ Returns (Last 30 days)'))->addClass('text-end'),
            Column::make('outstanding')->title(__('₹ Net Outstanding'))->addClass('text-end'),
            Column::make('overdue')->title(__('₹ Overdue'))->addClass('text-end'),
            Column::make('overdue_date')->title(__('Overdue (Since)'))->addClass('text-end'),
            Column::make('opening_balance')->title(__('₹ On Account (As of Today)'))->addClass('text-end'),
            // Column::make('opening_balance')->title(__('₹ Post Date Collection (Total)'))->addClass('text-end'),
            // Column::make('purchase_collection')->title(__('₹ Average Purchase (Value)'))->addClass('text-end'),
            Column::make('payment_collection')->title(__('₹ Pmt Made (CFY)'))->addClass('text-end'),
            Column::make('voucher_date')->title(__('Last Pmt (Done on)'))->addClass('text-end'),
            Column::make('phone_no')->title(__('Phone. No.')),
            Column::make('email')->title(__('Email')),
            Column::make('party_gst_in')->title(__('GSTIN'))->addClass('text-end'),
        ];
    }

    protected function filename(): string
    {
        return 'Faq_' . date('YmdHis');
    }
}
