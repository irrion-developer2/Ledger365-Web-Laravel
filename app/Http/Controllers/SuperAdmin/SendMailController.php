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

use App\Models\EmailLog;
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

                    ->whereIn('tally_ledgers.ledger_id', ['2', '3']) // Adjusted condition
                    // ->where('tally_vouchers.voucher_date',request()->date)
                    
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

                    // $credits = TallyVoucherHead::where('voucher_id', $ledger_data->voucher_id)
                    //                             ->where('entry_type', "credit")
                    //                             ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                    //                             ->select('tally_voucher_heads.amount')
                    //                             ->sum('tally_voucher_heads.amount');
                    // $curr_balance = TallyVoucher::join('tally_voucher_heads', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
                    //                             ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                    //                             ->where('tally_vouchers.voucher_date','<=',$ledger_data->voucher_date)
                    //                             ->where('tally_voucher_heads.ledger_id',$ledger_data->ledger_id)
                    //                             ->sum('tally_voucher_heads.amount');
                    // $curr_balance += $ledger_data->opening_balance;

                    return view('sendmails._action', compact('ledger_data'))->render();
                })
                ->make(true);
        }
        return view('sendmails.sendmail', compact('companys'));
    }

    public function viewMsg($voucher_id, $ledger_id) {

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
                                    'tally_companies.company_name',
                                    'tally_companies.address')
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
        return response()->json([
            'ledger_name' => $ledger_data->ledger_name,
            'voucher_date' => $ledger_data->voucher_date,
            'company_name' => $ledger_data->company_name,
            'credits' => $credits,
            'curr_balance' => $curr_balance,
        ]);
        // return redirect('sendmails._action', compact('credits','curr_balance'));
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
                                        'tally_companies.company_name',
                                        'tally_companies.address')
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

        if (!$ledger_data) {
            abort(404, 'Voucher not found');
        }
        // return view('sendmails.pdf', compact('ledger_data','credits'));
        $pdf = Pdf::loadView('sendmails.pdf', compact('ledger_data', 'credits','curr_balance','prev_balance'));
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

        $recipt_ledger_name = TallyLedger::join('tally_voucher_heads','tally_ledgers.ledger_id','=','tally_voucher_heads.ledger_id')
                                    ->where('tally_voucher_heads.voucher_id',$receipt->voucher_id)
                                    ->where('tally_voucher_heads.entry_type',"debit")
                                    ->select('tally_ledgers.ledger_name')
                                    ->first();

        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $curr_balance_words = ucwords($formatter->format($receipt->amount));

        $pdf = Pdf::loadView('sendmails.receipt', compact('receipt','curr_balance_words','recipt_ledger_name'));
        return $pdf->stream('receipt.pdf');
    }

    public function sendwhatsapp ($voucher_id,$ledger_id) {

        $response = $this->viewMsg($voucher_id,$ledger_id);
        $data = json_decode($response->getContent(), true);

        $voucherDate = date('F Y', strtotime($data['voucher_date']));
        $message = " Dear Member, {$data['ledger_name']}, bill for the month of <br> $voucherDate of <b>Rs {$data['credits']} </b> has been generated on {$data['voucher_date']}. <br> Total amount to be paid: <b>Rs {$data['curr_balance']}</b> on or before the due date.";
        log::info($message);

        $api_url = "https://wtconnects.com/api/2c90a3ce-87b6-48fc-a662-1f6d1afdb6ac/contact/send-message";
        $token = "leQPxk3RaLrgKgUar2yltxsKW9pf6BTTIGTsvUsZLx2S6ezMKoU8XIDpomvgLLV1";

        $request = [
            "from_phone_number_id" => "278340815355079",
            "phone_number" => "7990614523",
            "message_body" => $message
        ];
        $response = Http::withToken($token)
                        ->withoutVerifying()
                        ->post($api_url,$request);

        if ($response->successful()) {
            return redirect('sendmail')->with('success', "whatsapp message sent successfully.");
        } else {
            return redirect('sendmail')->with('error', "Failed to send whatsapp message");
        }                        

    }

    public function sendMails(Request $request, $send_voucher_id = null, $send_ledger_id = null) {

        if ($send_ledger_id && $send_voucher_id) {
            $ledger_datas = TallyLedger::join('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
                ->join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                ->join('tally_companies', 'tally_ledgers.company_id', '=', 'tally_companies.company_id')
                ->where('tally_voucher_heads.voucher_id', $send_voucher_id)
                ->where('tally_voucher_heads.ledger_id', $send_ledger_id)
                ->select(
                    'tally_ledgers.*',
                    'tally_voucher_heads.amount',
                    'tally_voucher_heads.voucher_id',
                    'tally_vouchers.voucher_date',
                    'tally_companies.company_name'
                )
                ->get();
        } else {
            $ledger_datas = TallyLedger::join('tally_ledger_groups', 'tally_ledgers.ledger_group_id', '=', 'tally_ledger_groups.ledger_group_id')
                ->whereIn('tally_ledger_groups.parent', ['Sundry Debtors', 'Sundry Creditors'])
                ->where('tally_ledgers.company_id', $request->company_id)
                ->join('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
                ->where('tally_voucher_heads.entry_type', "debit")
                ->join('tally_vouchers', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
                ->whereIn('tally_ledgers.ledger_id', ['2', '3'])
                // ->where('tally_vouchers.voucher_date',$request->date)
                ->join('tally_voucher_types', 'tally_voucher_types.voucher_type_id', '=', 'tally_vouchers.voucher_type_id')
                ->where('tally_voucher_types.parent', "Bill")
                ->join('tally_companies', 'tally_vouchers.company_id', '=', 'tally_companies.company_id')
                ->select(
                    'tally_ledgers.*',
                    'tally_voucher_heads.amount',
                    'tally_voucher_heads.voucher_id',
                    'tally_vouchers.voucher_date',
                    'tally_companies.company_name'
                )
                ->orderBy('tally_ledgers.ledger_name', 'asc')
                ->get();
        }
    
        if (!$ledger_datas || $ledger_datas->isEmpty()) {
            return redirect()->back()->with('error', 'No ledger data available to send emails.');
        }

        $email_count = 0;
        foreach ($ledger_datas as $ledger_data) {
            if (empty($ledger_data->email)) {
                Log::warning("Skipping ledger ID {$ledger_data->ledger_id} due to missing email.");
                continue;
            }

            $pdfContent = $this->viewPdf($ledger_data->voucher_id, $ledger_data->ledger_id);
            if (!$pdfContent) {
                Log::error("Failed to generate PDF for voucher ID {$ledger_data->voucher_id} and ledger ID {$ledger_data->ledger_id}.");
                continue;
            }
             // Save PDF to project folder
            $folderPath = storage_path('app/public/vouchers'); // Define folder path
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true); 
            }
            $fileName = "voucher_{$ledger_data->voucher_id}_{$ledger_data->ledger_id}.pdf";
            $filePath = $folderPath . '/' . $fileName;

            file_put_contents($filePath, $pdfContent); 
            log::info($fileName);
            log::info($filePath);
            $pdf = base64_encode($pdfContent);

            $responsem = $this->viewMsg($ledger_data->voucher_id,$ledger_data->ledger_id);
            $data = json_decode($responsem->getContent(), true);

            $formatDate = date('F Y', strtotime($data['voucher_date']));
            $message = "
                Dear Member, {$data['ledger_name']}, bill for the month of <br>
                $formatDate of <b>Rs {$data['credits']}</b> has been generated on {$data['voucher_date']}. <br>
                Total amount to be paid <b>Rs {$data['curr_balance']}</b> on or before the due date. <br>
                {$data['company_name']}
            ";

            // log::info($message);
            $subject = "Bill for the month of $formatDate";
    
            $postData = [
                "from" => ["address" => "noreply@irrion.in"],
                "to" => [["email_address" => ["address" => $ledger_data->email]]],
                "subject" => $subject,
                "htmlbody" => $message,
                "attachments" => [["name" => "voucher.pdf", "content" => $pdf, "mime_type" => "application/pdf"]]
            ];
    
            $jsonPostData = json_encode($postData);
            $curl = curl_init();
    
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.zeptomail.com/v1.1/email",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $jsonPostData,
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "authorization: Zoho-enczapikey wSsVR60lqUSiXacsmzSuI+04mllSVgnxFU17iQH063CtGvDH8Mc4lRDIU1OgSaceFzVgE2cXp78tnk8FhGFY3osvylgGWiiF9mqRe1U4J3x17qnvhDzNXG1ekBaLLIsAzwpikmJlF88n+g==",
                    "content-type: application/json",
                ],
            ]);
    
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
    
            if ($err) {
                Log::error("cURL Error for ledger ID {$ledger_data->ledger_id}: $err");
                continue; 
            }
    
            $responseDecoded = json_decode($response, true);
            // if (!isset($responseDecoded['data'])) {
            //     $errorMessage = $responseDecoded['message'] ?? 'Unknown error occurred.';
            //     Log::error("ZeptoMail API Error for ledger ID {$ledger_data->ledger_id}: " . $errorMessage);
            //     continue; // Skip incrementing email_count for failed responses
            // }
            if (isset($responseDecoded['data'])) {
                $email_count++;
            }
            Log::info("Successfully sent email for ledger ID {$ledger_data->ledger_id}. Emails sent count: $email_count");
        }
    
        Log::info("Total successfully sent emails: $email_count");
        return redirect()->back()->with('success', "Total successfully sent emails: $email_count");
    
        // if ($email_count > 0) {
        //     $message = "{$email_count} emails have been successfully sent!";
        //     return redirect()->back()->with('success', $message);
        // } else {
        //     return redirect()->back()->with('error', 'No emails were sent due to errors.');
        // }

    }

}