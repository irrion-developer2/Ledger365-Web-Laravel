<?php

namespace App\DataTables\SuperAdmin;

use Carbon\Carbon;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use App\Facades\UtilityFacades;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SalesDataTable extends DataTable
{

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
            ->addColumn('debit', function ($entry) {
                // Return the debit amount if the entry type is debit
                return $entry->entry_type === 'debit' ? number_format(abs($entry->amount), 2, '.', '') : '-';
            })
            ->addColumn('parent', function ($entry) {
                return $entry->parent;
            })
            ->addColumn('igst_amount', function ($entry) {
                return number_format($entry->igst_amount ?? 0, 2, '.', '');
            })
            ->addColumn('round_off_amount', function ($entry) {
                return number_format($entry->round_off_amount ?? 0, 2, '.', '');
            })
            ->addColumn('pending_amount', function ($entry) {
                // $voucherHeads = TallyVoucherHead::where('tally_voucher_id', $entry->id)->get();
                $pendingVoucherHeads = TallyVoucherHead::where('ledger_name', $entry->party_ledger_name)->get();


                $debitAmount = 0;
                $creditAmount = 0;
            
                foreach ($pendingVoucherHeads as $head) {
                    if ($head->entry_type === 'debit') {
                        $debitAmount += $head->amount;
                    } elseif ($head->entry_type === 'credit') {
                        $creditAmount += $head->amount;
                    }
                }
            
                $pendingAmount = $debitAmount + $creditAmount ; // calculate the difference
                return number_format($pendingAmount, 2, '.', '');
            })            
            // ->addColumn('party_ledger_name', function ($entry) {
            //     return '<a href="' . route('sales.items', ['SaleItem' => $entry->id]) . '">' . $entry->party_ledger_name . '</a>';
            // })
            ->addColumn('voucher_number', function ($entry) {
                return '<a href="' . route('sales.items', ['SaleItem' => $entry->id]) . '">' . $entry->voucher_number . '</a>';
            })
            // render: function(data, type, row) {
            //     return '<a href="{{ url('reports/VoucherItem') }}/' + row.tally_voucher_id + '">' + data + '</a>';
            // } 
            ->addColumn('voucher_date', function ($entry) {
                return Carbon::parse($entry->voucher_date)->format('j M Y');
            })
            ->addColumn('gst_in', function ($entry) {
                return $entry->gst_in;
            })
            ->addColumn('due_date', function ($entry) {
                $creditPeriod  = intval($entry->bill_credit_period);
                $voucherDate   = Carbon::parse($entry->voucher_date);
                $voucherCredit = $voucherDate->addDays($creditPeriod)->format('j M Y');
                return $voucherCredit;
            })
            ->addColumn('overdue_day', function ($entry) {
                $creditPeriod  = intval($entry->bill_credit_period);
                $voucherDate   = Carbon::parse($entry->voucher_date);
                $dueDate       = $voucherDate->addDays($creditPeriod);
                $today         = Carbon::now();
                
                $overdueDays   = $today->gt($dueDate) ? $today->diffInDays($dueDate) : 0;
                $overdueMonths = $today->diffInMonths($dueDate);
                
                if ($overdueMonths > 0) {
                    $formatted = "{$overdueMonths} Months";
                } elseif ($overdueDays > 0) {
                    $formatted = "{$overdueDays} Days";
                } else {
                    $formatted = 'On Time';
                }
                return $overdueDays > 0 || $overdueMonths > 0 ? $formatted : $formatted;
            })
            
            
            ->rawColumns(['voucher_number']);
    }

    public function query(TallyVoucher $model)
    {
        $query = $model->newQuery()
            ->select('tally_vouchers.*', 'tally_voucher_heads.entry_type', 'tally_voucher_heads.amount', 'tally_ledgers.parent', 'tally_ledgers.bill_credit_period', 'tally_ledgers.gst_in', 'tally_ledgers.phone_no', 'tally_ledgers.email')
            ->leftJoin('tally_voucher_heads', function($join) {
                $join->on('tally_vouchers.party_ledger_name', '=', 'tally_voucher_heads.ledger_name')
                    ->on('tally_vouchers.id', '=', 'tally_voucher_heads.tally_voucher_id');
            })
            ->leftJoin('tally_ledgers', 'tally_vouchers.party_ledger_name', '=', 'tally_ledgers.language_name')
            ->leftJoin('tally_voucher_heads as related_heads', 'tally_voucher_heads.tally_voucher_id', '=', 'related_heads.tally_voucher_id')
            ->groupBy('tally_vouchers.id', 'tally_vouchers.party_ledger_name', 'tally_vouchers.voucher_date', 'tally_vouchers.voucher_number', 'tally_vouchers.voucher_type', 'tally_ledgers.parent', 'tally_ledgers.bill_credit_period', 'tally_ledgers.gst_in', 'tally_ledgers.phone_no', 'tally_ledgers.email', 'tally_voucher_heads.entry_type', 'tally_voucher_heads.amount')
            ->selectRaw('GROUP_CONCAT(DISTINCT related_heads.ledger_name) as related_ledger_names')
            ->selectRaw('SUM(CASE WHEN related_heads.ledger_name LIKE "%IGST @18%" THEN related_heads.amount ELSE 0 END) as igst_amount')
            ->selectRaw('SUM(CASE WHEN related_heads.ledger_name LIKE "%Round Off%" THEN related_heads.amount ELSE 0 END) as round_off_amount');

        $query->where('tally_vouchers.voucher_type', 'Sales');

        if (request()->has('start_date') && request()->has('end_date')) {
            $startDate = request('start_date');
            $endDate = request('end_date');

            if ($startDate && $endDate) {
                try {
                    $startDate = Carbon::parse($startDate)->startOfDay();
                    $endDate = Carbon::parse($endDate)->endOfDay();
                    $query->whereBetween('voucher_date', [$startDate, $endDate]);
                } catch (\Exception $e) {
                    \Log::error('Date parsing error: ' . $e->getMessage());
                }
            }
        }

        // Handling search parameters
        $search = request()->input('search.value');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('tally_vouchers.party_ledger_name', 'like', "%{$search}%")
                  ->orWhere('tally_ledgers.parent', 'like', "%{$search}%")
                  ->orWhere('tally_vouchers.voucher_number', 'like', "%{$search}%")
                  ->orWhere('tally_vouchers.voucher_date', 'like', "%{$search}%")
                  ->orWhere('tally_ledgers.gst_in', 'like', "%{$search}%")
                  ->orWhere('tally_ledgers.phone_no', 'like', "%{$search}%")
                  ->orWhere('tally_ledgers.email', 'like', "%{$search}%");
            });
        }

        return $query;
    }


    public function html()
    {
        return $this->builder()
            ->setTableId('sales-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(3)
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
            Column::make('party_ledger_name')->title(__('Name'))->addClass('fixed-column'),
            Column::make('parent')->title(__('Group')),
            Column::make('voucher_date')->title(__('Invoice Date')),
            Column::make('voucher_number')->title(__('Invoice Number')),
            Column::make('debit')->title(__('Invoice Amount')),
            // Column::make('pending_amount')->title(__('Pending Amount')),
            // Column::make('due_date')->title(__('Due Date')),
            // Column::make('overdue_day')->title(__('Overdue By Days'))->addClass('text-center text-danger'),
            Column::make('gst_in')->title(__('GSTIN')),
            Column::make('place_of_supply')->title(__('Place Of Supply')),
            Column::make('phone_no')->title(__('Phone No'))->searchable(false),
            Column::make('email')->title(__('Email'))->searchable(false),
            Column::make('narration')->title(__('Narration')),
        ];
    }

    protected function filename(): string
    {
        return 'Faq_' . date('YmdHis');
    }
}
