<?php

namespace App\Http\Controllers\SuperAdmin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\SuperAdmin\SendMailDataTable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use NumberFormatter;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade;


use App\Models\TallyCompany;
use App\Models\TallyLedger;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucher;

class SendMailController extends Controller
{
    public function sendmail (Request $request) {
        $companys = TallyCompany::get();

        if ($request->ajax()) {
            if(request()->company_id && request()->date) {
                $ledger_data = TallyLedger::
                    join('tally_ledger_groups','tally_ledgers.ledger_group_id','=','tally_ledger_groups.ledger_group_id')
                    // ->where('tally_ledgers.parent','tally_ledger_groups.ledger_group_name')
                    ->whereIn('tally_ledger_groups.parent', ['Sundry Debtors', 'Sundry Creditors'])

                    ->where('tally_ledgers.company_id',request()->company_id)
                    ->join('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
                    ->where('tally_voucher_heads.entry_type',"debit")
                    ->join('tally_vouchers', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
                    ->where('tally_vouchers.voucher_date',request()->date)
                    ->join('tally_voucher_types', 'tally_voucher_types.voucher_type_id', '=', 'tally_vouchers.voucher_type_id')
                    ->where('tally_voucher_types.parent',"Bill")
                    ->join('tally_companies', 'tally_vouchers.company_id', '=', 'tally_companies.company_id')
                    ->select('tally_ledgers.*',
                            'tally_voucher_heads.amount',
                            'tally_voucher_heads.voucher_id',
                            'tally_vouchers.voucher_date',
                            'tally_companies.company_name')
                    ->orderBy('tally_ledgers.ledger_name', 'asc');
            } else {
                $ledger_data = TallyLedger::join('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
                    ->whereRaw('1 = 0');
            }
            return DataTables::of($ledger_data)
                ->addColumn('action', function ($ledger_data) {

                    $credits = TallyVoucherHead::where('voucher_id', $ledger_data->voucher_id)
                                                ->where('entry_type', "credit")
                                                ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                                                ->select('tally_voucher_heads.amount')
                                                ->sum('tally_voucher_heads.amount');
                    $curr_balance = TallyVoucher::join('tally_voucher_heads', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
                                                ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                                                ->where('tally_vouchers.voucher_date','<=',$ledger_data->voucher_date)
                                                ->where('tally_voucher_heads.ledger_id',$ledger_data->ledger_id)
                                                ->sum('tally_voucher_heads.amount');
                    $curr_balance += $ledger_data->opening_balance;

                    return view('sendmails._action', compact('ledger_data','credits','curr_balance'))->render();
                })
                ->make(true);
        }
        return view('sendmails.sendmail', compact('companys'));
    }

    public function viewPdf($voucher_id, $ledger_id)
    {
        $ledger_data = TallyLedger::join('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
                                    ->join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                                    ->join('tally_companies', 'tally_ledgers.company_id', '=', 'tally_companies.company_id')
                                    ->where('tally_voucher_heads.voucher_id', $voucher_id)
                                    ->where('tally_ledgers.ledger_id', $ledger_id)
                                    ->select('tally_ledgers.ledger_name',
                                        'tally_ledgers.ledger_id',
                                        'tally_ledgers.opening_balance',
                                        'tally_ledgers.alias1',
                                        'tally_voucher_heads.voucher_id',
                                        'tally_vouchers.voucher_date',
                                        'tally_vouchers.voucher_number',
                                        'tally_companies.company_name')
                                    ->first();

        $credits = TallyVoucherHead::where('voucher_id', $ledger_data->voucher_id)
                            ->where('entry_type', "credit")
                            ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                            ->select('tally_ledgers.ledger_name','tally_voucher_heads.amount')
                            ->get();

        $prev_balance = TallyVoucher::join('tally_voucher_heads', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
                                    ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                                    ->where('tally_vouchers.voucher_date','<',$ledger_data->voucher_date)
                                    ->where('tally_voucher_heads.ledger_id',$ledger_data->ledger_id)
                                    ->sum('tally_voucher_heads.amount');
        $prev_balance += $ledger_data->opening_balance;

        $curr_balance = TallyVoucher::join('tally_voucher_heads', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
                                    ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                                    ->where('tally_vouchers.voucher_date','<=',$ledger_data->voucher_date)
                                    ->where('tally_voucher_heads.ledger_id',$ledger_data->ledger_id)
                                    ->sum('tally_voucher_heads.amount');
        $curr_balance += $ledger_data->opening_balance;
        // Function to convert number to words
        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $curr_balance_words = ucwords($formatter->format($curr_balance));

        if (!$ledger_data) {
            abort(404, 'Voucher not found');
        }
        // return view('sendmails.pdf', compact('ledger_data','credits'));
        $pdf = Pdf::loadView('sendmails.pdf', compact('ledger_data', 'credits','curr_balance','prev_balance','curr_balance_words'));
        return $pdf->stream('voucher.pdf');
    }

    public function viewReceipt($voucher_id, $ledger_id) {
        $curr_voucher = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                                        ->where('tally_vouchers.voucher_id', $voucher_id)
                                        ->where('tally_voucher_heads.ledger_id', $ledger_id)
                                        ->first();

        $receipt = TallyVoucher::join('tally_voucher_types','tally_vouchers.voucher_type_id','=','tally_voucher_types.voucher_type_id')
                                ->join('tally_voucher_heads','tally_vouchers.voucher_id','=','tally_voucher_heads.voucher_id')
                                ->join('tally_ledgers', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
                                ->join('tally_companies', 'tally_ledgers.company_id', '=', 'tally_companies.company_id')
                                ->where('tally_voucher_heads.ledger_id', $ledger_id)
                                ->where('tally_voucher_types.voucher_type_name',"Receipt")
                                ->where('tally_vouchers.voucher_date','<',$curr_voucher->voucher_date)
                                ->orderBy('tally_vouchers.voucher_date','desc')
                                ->first();
        if($receipt) {
            $recipt_ledger_name = TallyLedger::join('tally_voucher_heads','tally_ledgers.ledger_id','=','tally_voucher_heads.ledger_id')
                                        ->where('tally_voucher_heads.voucher_id',$receipt->voucher_id)
                                        ->where('tally_voucher_heads.entry_type',"debit")
                                        ->select('tally_ledgers.ledger_name')
                                        ->first();

            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $curr_balance_words = ucwords($formatter->format($receipt->amount));

            $pdf = Pdf::loadView('sendmails.receipt', compact('receipt','curr_balance_words','recipt_ledger_name'));
            return $pdf->stream('receipt.pdf');
            // return view('sendmails.receipt',compact('receipt','curr_balance_words','recipt_ledger_name'));

        } else {
            return redirect()->back()->with('error', 'There are no last receipts');
        }
    }

    // public function sendmailtouser (Request $request) {
   
    
    public function sendmailtouser (Request $request) {
        // $emailDataArray = [];
        // $sentEmails = 0;
        // $responseMessages = [];
        
        $voucher_id = $request->query('voucher_id');
        $ledger_id = $request->query('ledger_id');

        $pdfContent = $this->viewPdf($voucher_id,$ledger_id);
        $pdf = base64_encode($pdfContent);

        $ledger_data = TallyLedger::join('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
                                    ->join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                                    ->join('tally_companies', 'tally_ledgers.company_id', '=', 'tally_companies.company_id')
                                    ->where('tally_voucher_heads.voucher_id', $voucher_id)
                                    ->select('tally_ledgers.ledger_name',
                                        'tally_ledgers.ledger_id',
                                        'tally_ledgers.email',
                                        'tally_ledgers.opening_balance',
                                        'tally_voucher_heads.voucher_id',
                                        'tally_vouchers.voucher_date',
                                        'tally_companies.company_name')
                                    ->first();
        $credits = TallyVoucherHead::where('voucher_id', $ledger_data->voucher_id)
                                    ->where('entry_type', "credit")
                                    ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                                    ->select('tally_voucher_heads.amount')
                                    ->sum('tally_voucher_heads.amount');

        $curr_balance = TallyVoucher::join('tally_voucher_heads', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
                                    ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                                    ->where('tally_vouchers.voucher_date','<=',$ledger_data->voucher_date)
                                    ->where('tally_voucher_heads.ledger_id',$ledger_data->ledger_id)
                                    ->sum('tally_voucher_heads.amount');

        $curr_balance += $ledger_data->opening_balance;

        $format_date = date('F Y', strtotime($ledger_data->voucher_date));
        $message = "
            Dear Member, {$ledger_data->ledger_name}, bill for the month of <br>
            $format_date of <b>Rs $credits</b> has been generated on {$ledger_data->voucher_date}. <br>
            Total amount to be paid <b>Rs $curr_balance</b> on or before the due date. <br>
            {$ledger_data->company_name}
        ";
        $subject = "bill for the month of $format_date";

        $postData = [
            "from" => ["address" => "noreply@irrion.in"],
            "to" => [
                [
                    "email_address" => ["address" => $ledger_data->email]
                ]
            ],
            "subject" => $subject,
            "htmlbody" => $message,
            "attachments" => [
                [
                    "name" => "voucher.pdf",
                    "content" => $pdf,
                    "mime_type" => "application/pdf"
                ]
            ]
        ];
        $jsonPostData = json_encode($postData);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.zeptomail.com/v1.1/email",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonPostData,
                    CURLOPT_HTTPHEADER => array(
                        "accept: application/json",
                        "authorization: Zoho-enczapikey wSsVR60lqUSiXacsmzSuI+04mllSVgnxFU17iQH063CtGvDH8Mc4lRDIU1OgSaceFzVgE2cXp78tnk8FhGFY3osvylgGWiiF9mqRe1U4J3x17qnvhDzNXG1ekBaLLIsAzwpikmJlF88n+g==",
                        "cache-control: no-cache",
                        "content-type: application/json",
                    ),
                ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }

         // public function sendmailtouser (Request $request) {
    //     $emailDataArray = [];
    //     $sentEmails = 0;
    //     $responseMessages = [];

    //     $htmlContent = "
    //         <html>
    //             <head>
    //                 <style>
    //                     body {
    //                         font-family: Arial, sans-serif;
    //                         margin: 20px;
    //                     }
    //                     .header {
    //                         text-align: center;
    //                         margin-bottom: 20px;
    //                     }
    //                     .header h1 {
    //                         font-size: 24px;
    //                         margin: 0;
    //                         font-weight: bold;
    //                     }
    //                     .header p {
    //                         margin: 5px 0;
    //                         font-size: 14px;
    //                     }
    //                     .name-date{
    //                         width: 100%;
    //                     }
    //                     .name{
    //                         text-align: left;
    //                     }
    //                     .date{
    //                         text-align: right;
    //                     }
    //                     .amount-table {
    //                         width: 100%;
    //                         border-collapse: collapse;
    //                         margin-top: 20px;
    //                     }
    //                     .amount-table thead, .amount-table tfoot {
    //                         border-top: 1px solid #000;
    //                         border-bottom: 1px solid #000;
    //                     }
    //                     .amount-table tr td:first-child, .amount-table tr th:first-child {
    //                         text-align: left;
    //                     }
    //                     .amount-table tr td:last-child, .amount-table tr th:last-child {
    //                         text-align: right;
    //                     }
    //                     .amount-summary {
    //                         margin-top: 20px;
    //                         font-size: 14px;
    //                         font-weight: normal;
    //                     }
    //                     .amount-summary p{
    //                         margin-bottom: 0;
    //                     }
    //                     .footer {
    //                         text-align: right;
    //                     }
    //                     .footer p{
    //                         text-transform: uppercase;
    //                         margin-top: 30px;
    //                         font-size: 22px;
    //                     }
    //                 </style>
    //             </head>
    //             <body>
    //                 <div class='header'>
    //                     <h1>{companyName}</h1>
    //                     <p>Month : {formattedDate}</p>
    //                     <table class='name-date'>
    //                         <tbody>
    //                             <tr>
    //                                 <td class='name'><strong>{ledgerName}</strong></td>
    //                                 <td class='date'>{voucherDate}</td>
    //                             </tr>
    //                         </tbody>
    //                     </table>
    //                 </div>
    //                 <table class='amount-table'>
    //                     <thead>
    //                         <tr>
    //                             <th>Particular</th>
    //                             <th>Amount</th>
    //                         </tr>
    //                     </thead>
    //                     <tbody>
    //                         <tr>
    //                             <td>Cont. Service Charges</td>
    //                             <td></td>
    //                         </tr>
    //                         <tr>
    //                             <td>Additional Water Charges</td>
    //                             <td></td>
    //                         </tr>
    //                         <tr>
    //                             <td>Cont. Sinking Fund</td>
    //                             <td></td>
    //                         </tr>
    //                         <tr>
    //                             <td>Cont. Bldg. Rep. Fund</td>
    //                             <td></td>
    //                         </tr>
    //                     </tbody>
    //                     <tfoot>
    //                         <tr>
    //                             <td>Total:</td>
    //                             <td>{amount}</td>
    //                         </tr>
    //                         <tr>
    //                             <td>Previous Dues</td>
    //                             <td>{previousAmount}</td>
    //                         </tr>
    //                         <tr>
    //                             <td>Grand Total:</td>
    //                             <td>{totalAmount}</td>
    //                         </tr>
    //                     </tfoot>
    //                 </table>
    //                 <div class='amount-summary'>
    //                     <p>1. If any discrepancy Found in Bill, Kindly Advice Committee.</p>
    //                     <p>2. Bill to be Paid by Due Date.</p>
    //                 </div>
    //                 <div class='footer'>
    //                     <p>from {companyName}</p>
    //                 </div>
    //             </body>
    //         </html>
    //     ";

    //     if($request->input('company_id') && $request->input('date')) {
    //         $ledger_datas = TallyLedger::where('tally_ledgers.company_id',$request->input('company_id'))
    //                                 ->whereIn('tally_ledgers.ledger_group_id', [26, 27])
    //                                 ->join('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
    //                                 ->join('tally_vouchers', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
    //                                 ->join('tally_companies', 'tally_vouchers.company_id', '=', 'tally_companies.company_id')
    //                                 ->where('tally_vouchers.voucher_date',$request->input('date'))
    //                                 ->select('tally_ledgers.*','tally_voucher_heads.amount','tally_vouchers.voucher_date','tally_companies.company_name')
    //                                 ->orderBy('tally_vouchers.voucher_date', 'asc')
    //                                 ->get();
    //         foreach($ledger_datas as $ledger_data) {
    //             $email = $ledger_data->email;

    //             $previous_amount = TallyLedger::where('tally_ledgers.ledger_id',$ledger_data->ledger_id)
    //                                 ->join('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
    //                                 ->join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
    //                                 ->where('tally_vouchers.voucher_date','<',$ledger_data->voucher_date)
    //                                 ->sum('tally_voucher_heads.amount');

    //             $total_amount = TallyLedger::where('tally_ledgers.ledger_id',$ledger_data->ledger_id)
    //                                 ->join('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
    //                                 ->join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
    //                                 ->where('tally_vouchers.voucher_date','<=',$ledger_data->voucher_date)
    //                                 ->sum('tally_voucher_heads.amount');

    //             $formattedDate = Carbon::parse($ledger_data->voucher_date)->format('F Y');
    //             $subject = 'bill for the month of'.$formattedDate;

    //             $content = 'Dear Member, ' . $ledger_data->ledger_name . ', bill for the month of ' . $formattedDate . ' of <b>Rs ' . $ledger_data->amount . '</b> has been generated on ' . $ledger_data->voucher_date . ' with previous due of <b>Rs ' . $previous_amount . '</b>. Total amount to be paid <b>Rs ' . $total_amount . '</b> on or before the due date. ' . $ledger_data->company_name;

    //             $htmlFinalContent = str_replace(
    //                 ['{subject}', '{formattedDate}', '{voucherDate}', '{ledgerName}', '{amount}', '{previousAmount}', '{totalAmount}', '{companyName}'],
    //                 [$subject, $formattedDate, $ledger_data->voucher_date, $ledger_data->ledger_name, $ledger_data->amount, $previous_amount, $total_amount, $ledger_data->company_name],
    //                 $htmlContent
    //             );
                
    //             $pdf = Pdf::loadHTML($htmlFinalContent);
    //             $pdf_data = base64_encode($pdf->output());

    //             $emailDataArray[] = [
    //                 "email" => $email,
    //                 "subject" => $subject,
    //                 "content" => $content,
    //                 "pdf" => $pdf_data,
    //             ];
    //         }
    //     } else {

    //         $htmlFinalContent = str_replace(
    //             ['{subject}', '{formattedDate}', '{voucherDate}', '{ledgerName}', '{amount}', '{previousAmount}', '{totalAmount}', '{companyName}'],
    //             [
    //                 $request->input('subject'),
    //                 $request->input('formattedDate'),
    //                 $request->input('voucherDate'),
    //                 $request->input('ledgerName'),
    //                 $request->input('amount'),
    //                 $request->input('previous_amount'),
    //                 $request->input('total_amount'),
    //                 $request->input('companyName'),
    //             ],
    //             $htmlContent
    //         );
                
    //         $pdf = Pdf::loadHTML($htmlFinalContent);
    //         $pdf_data = base64_encode($pdf->output());

    //         $emailDataArray[] = [
    //             "email" => $request->input('email'),
    //             "subject" => $request->input('subject'),
    //             "content" => $request->input('content'),
    //             "pdf" => $pdf_data,
    //         ];
    //     }

    //     foreach ($emailDataArray as $emailData) {            
    //         $curl = curl_init();
    //         $payload = json_encode([
    //             "from" => [
    //                 "address" => "noreply@irrion.in"
    //             ],
    //             "to" => [
    //                 ["email_address" => ["address" => $emailData['email']]]
    //             ],
    //             "bounce_address" => "noreply@bounce-zem.irrion.in",
    //             "subject" => $emailData['subject'],
    //             "htmlbody" => $emailData['content'],
    //             "attachments" => [[
    //                 "name" => $emailData['subject'] . ".pdf",
    //                 "content" => $emailData['pdf'],
    //                 "mime_type" => "application/pdf"
    //             ]]
    //         ]);
    
    //         curl_setopt_array($curl, [
    //             CURLOPT_URL => "https://api.zeptomail.com/v1.1/email",
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => "",
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 30,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => "POST",
    //             CURLOPT_POSTFIELDS => $payload,
    //             CURLOPT_HTTPHEADER => [
    //                 "accept: application/json",
    //                 "authorization: Zoho-enczapikey wSsVR60lqUSiXacsmzSuI+04mllSVgnxFU17iQH063CtGvDH8Mc4lRDIU1OgSaceFzVgE2cXp78tnk8FhGFY3osvylgGWiiF9mqRe1U4J3x17qnvhDzNXG1ekBaLLIsAzwpikmJlF88n+g==",
    //                 "cache-control: no-cache",
    //                 "content-type: application/json",
    //             ],
    //         ]);
    
    //         $response = curl_exec($curl);
    //         $err = curl_error($curl);
    
    //         curl_close($curl);
    
    //         if ($err) {
    //             $responseMessages[] = "Error sending to " . $emailData['email'] . ": cURL Error #: $err";
    //         } else {
    //             $response_data = json_decode($response, true);
    //             if (isset($response_data['data'])) {
    //                 $sentEmails++;
    //                 $responseMessages[] = $emailData['email'];
    //             }
    //         }
    //         // if ($err) {
    //         //     return response()->json([
    //         //         'status' => 'error',
    //         //         'message' => "cURL Error #: $err"
    //         //     ]);
    //         // } else {
    //         //     $response_data = json_decode($response, true);
    //         //     if (!isset($response_data['data'])) {
    //         //         return response()->json([
    //         //             'status' => 'error',
    //         //             'message' => 'Failed to send some emails.',
    //         //         ]);
    //         //     }
    //         // }
    //     }
    //     if($sentEmails == 0) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => "failed to send mail",
    //         ]);
    //     } else {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Email successfully sent to " . implode(', ', $responseMessages),
    //         ]);
    //     }
        
    //     // return response()->json([
    //     //     'status' => 'success',
    //     //     'message' => 'Email(s) sent successfully!',
    //     // ]);
    // }

        // if($request->input('company_id') && $request->input('date')) {
        //     foreach ($emailDataArray as $emailData) {            
        //         $curl = curl_init();
        //         $payload = json_encode([
        //             "from" => [
        //                 "address" => "noreply@irrion.in"
        //             ],
        //             "to" => [
        //                 ["email_address" => ["address" => $emailData['email']]]
        //             ],
        //             "bounce_address" => "noreply@bounce-zem.irrion.in",
        //             "subject" => $emailData['subject'],
        //             "htmlbody" => $emailData['content'],
        //             "attachments" => [[
        //                 "name" => $emailData['subject'] . ".pdf",
        //                 "content" => $emailData['pdf'],
        //                 "mime_type" => "application/pdf"
        //             ]]
        //         ]);
        
        //         curl_setopt_array($curl, [
        //             CURLOPT_URL => "https://api.zeptomail.com/v1.1/email",
        //             CURLOPT_RETURNTRANSFER => true,
        //             CURLOPT_ENCODING => "",
        //             CURLOPT_MAXREDIRS => 10,
        //             CURLOPT_TIMEOUT => 30,
        //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //             CURLOPT_CUSTOMREQUEST => "POST",
        //             CURLOPT_POSTFIELDS => $payload,
        //             CURLOPT_HTTPHEADER => [
        //                 "accept: application/json",
        //                 "authorization: Zoho-enczapikey wSsVR60lqUSiXacsmzSuI+04mllSVgnxFU17iQH063CtGvDH8Mc4lRDIU1OgSaceFzVgE2cXp78tnk8FhGFY3osvylgGWiiF9mqRe1U4J3x17qnvhDzNXG1ekBaLLIsAzwpikmJlF88n+g==",
        //                 "cache-control: no-cache",
        //                 "content-type: application/json",
        //             ],
        //         ]);
        
        //         $response = curl_exec($curl);
        //         $err = curl_error($curl);
        
        //         curl_close($curl);
        
        //         if ($err) {
        //             $responseMessages[] = "Error sending to " . $emailData['email'] . ": cURL Error #: $err";
        //         } else {
        //             $response_data = json_decode($response, true);
        //             if (isset($response_data['data'])) {
        //                 $sentEmails++;
        //                 $responseMessages[] = $emailData['email'];
        //             }
        //         }
        //     }
        //     if($sentEmails == 0) {
        //         return response()->json([
        //             'status' => 'error',
        //             'message' => "failed to send mail",
        //         ]);
        //     } else {
        //         return response()->json([
        //             'status' => 'success',
        //             'message' => "Email successfully sent to " . implode(', ', $responseMessages),
        //         ]);
        //     }
        // }
    }

    // public function sendmail (Request $request) {
    //     $curl = curl_init();
        
    //     $mail_content = MailContent::first();
    //     $attachment = $mail_content->attachment;
    //     $filePaths = explode(",", $attachment);

    //     $attachments = [];
    //     foreach ($filePaths as $filePath) {
    //         $fileData = base64_encode(file_get_contents($filePath));
    //         $fileName = basename($filePath);
    
    //         $attachments[] = [
    //             "name" => $fileName,
    //             "mime_type" => "application/pdf",
    //             "content" => $fileData
    //         ];
    //     }
        
    //     $users = User::all('email');
    //     foreach($users as $user) {
    //         $emailData = json_encode([
    //             "from" => [
    //                 "address" => $mail_content->email
    //             ],
    //             "to" => [
    //                 ["email_address" => ["address" => $user->email]]
    //             ],
    //             "bounce_address" => "noreply@bounce-zem.irrion.in",
    //             "subject" => "Test Email with Multiple PDF Attachments",
    //             "htmlbody" => "<div><b>$mail_content->message</b></div>",
    //             "attachments" => $attachments
    //         ]);
            
    //         curl_setopt_array($curl, array(
    //             CURLOPT_URL => "https://api.zeptomail.com/v1.1/email",
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => "",
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 30,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => "POST",
    //             CURLOPT_POSTFIELDS => $emailData,
    //             CURLOPT_HTTPHEADER => array(
    //                 "accept: application/json",
    //                 "authorization: Zoho-enczapikey wSsVR60lqUSiXacsmzSuI+04mllSVgnxFU17iQH063CtGvDH8Mc4lRDIU1OgSaceFzVgE2cXp78tnk8FhGFY3osvylgGWiiF9mqRe1U4J3x17qnvhDzNXG1ekBaLLIsAzwpikmJlF88n+g==",
    //                 "cache-control: no-cache",
    //                 "content-type: application/json",
    //             ),
    //         ));
            
    //         $err = curl_error($curl);
    //         $response = curl_exec($curl);
            
    //         $responseData = json_decode($response, true);
    //         $mail_log = json_decode($emailData);
            
    //         $data = SendMail::create([
    //             'recipient_email' => $user->email,
    //             'sender_email' => $mail_log->from->address,
    //             'subject' => $mail_log->subject,
    //             'content' => $mail_log->htmlbody,
    //             'send_status' => $responseData['message'],
    //             'note' => $err ? $err : $response,
    //             'error_message' => $err,
    //             'has_attachment' => $attachment,
    //         ]);
    //         $allMailData[] = $data;
    //     }
    //     curl_close($curl);

    //     if ($err) {
    //         return response()->json(['error' => "cURL Error #: $err"], 500);
    //     } else {
    //         // return response()->json($responseData);
    //         return response()->json($allMailData);
    //     }
    // }

}
