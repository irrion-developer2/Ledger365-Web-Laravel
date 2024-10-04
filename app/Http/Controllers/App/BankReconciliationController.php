<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Smalot\PdfParser\Parser; 
use Spatie\PdfToText\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\BankReconciliation;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use Stancl\Tenancy\Facades\Tenancy;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BankReconciliationController extends Controller
{

    public function index()
    {
        return view('app.bankReconciliation.index');
    }
    
    public function uploadPdf(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|mimes:pdf|max:10000', 
        ]);

        $pdfPath = $request->file('pdf_file')->store('pdfs');

        $parser = new Parser();
        $pdf = $parser->parseFile(storage_path('app/' . $pdfPath));
        $text = $pdf->getText();

        Log::info('Raw Extracted Text: ', ['text' => $text]);

        if (empty(trim($text))) {
            return response()->json(['message' => 'No text extracted from PDF. Please check the file format.']);
        }

        $data = $this->processPdfDataToJson($text);

        Log::info('Processed Data: ', $data);

        if (empty($data)) {
            return response()->json(['message' => 'No valid data extracted. Please check the PDF format.']);
        }

        foreach ($data as $entry) {
            try {
                BankReconciliation::updateOrCreate([
                    'transaction_date' => $entry['transaction_date'],
                    'narration' => $entry['narration'],
                    'chq_ref_no' => $entry['chq_ref_no'],
                    'withdrawal' => $entry['withdrawal'],
                    'deposit' => $entry['deposit'],
                    'balance' => $entry['balance']
                ]);
                Log::info('Entry Saved: ', $entry);
            } catch (\Exception $e) {
                Log::error('Error Saving Entry: ', ['error' => $e->getMessage(), 'entry' => $entry]);
            }
        }

        // return response()->json(['message' => 'PDF data processed and stored successfully!']);
        return redirect()->back()->with('success', 'Bank statements imported successfully!');
    }

    private function processPdfDataToJson($text)
    {
        $lines = explode("\n", $text);
        $data = [];
        $currentEntry = [];
    
        foreach ($lines as $line) {
            // Skip lines that don't match the expected data pattern
            if (preg_match('/^(\d{2}-[A-Za-z]{3}-\d{4})/', $line, $matches)) {
                if (!empty($currentEntry)) {
                    $data[] = $currentEntry;
                    $currentEntry = [];
                }
    
                // Start a new entry
                $currentEntry['transaction_date'] = Carbon::createFromFormat('d-M-Y', $matches[1])->format('Y-m-d');
                $currentEntry['narration'] = trim(substr($line, strlen($matches[1])));
            } elseif (preg_match('/^([A-Za-z0-9\/]+)?\s*([A-Za-z0-9\/]+)?\s*([\d,\.]+)?\s*([\d,\.]+)?\s*([\d,\.]+)/', $line, $matches)) {
                // Process the details line
                $currentEntry['chq_ref_no'] = isset($matches[1]) ? $matches[1] : null;
                $currentEntry['withdrawal'] = isset($matches[3]) ? str_replace(',', '', $matches[3]) : null;
                $currentEntry['deposit'] = isset($matches[4]) ? str_replace(',', '', $matches[4]) : null;
                $currentEntry['balance'] = isset($matches[5]) ? str_replace(',', '', $matches[5]) : null;
            }
        }
    
        // Add the last entry
        if (!empty($currentEntry)) {
            $data[] = $currentEntry;
        }
    
        return $data;
    }
    

    
    // private function processPdfDataToJson($text)
    // {
    //     // Split text into lines
    //     $lines = explode("\n", $text);
    //     $data = [];
    
    //     foreach ($lines as $line) {
    //         if (preg_match('/(\d{2}-[A-Za-z]{3}-\d{4})\s+(.+?)\s+([A-Za-z0-9\/]+)?\s+([A-Za-z0-9\/]+)?\s*([\d,\.]+)?\s*([\d,\.]+)?\s*([\d,\.]+)/', $line, $matches)) {
    //             try {
    //                 // Convert date to MySQL-compatible format
    //                 $transactionDate = Carbon::createFromFormat('d-M-Y', $matches[1])->format('Y-m-d');
    //             } catch (\Exception $e) {
    //                 Log::error('Error Parsing Date: ' . $matches[1] . ' - ' . $e->getMessage());
    //                 continue; // Skip this entry if the date is invalid
    //             }
    
    //             $data[] = [
    //                 'transaction_date' => $transactionDate,
    //                 'narration' => $matches[2],
    //                 'chq_ref_no' => isset($matches[3]) ? $matches[3] : null,
    //                 'withdrawal' => isset($matches[5]) ? str_replace(',', '', $matches[5]) : null,
    //                 'deposit' => isset($matches[6]) ? str_replace(',', '', $matches[6]) : null,
    //                 'balance' => isset($matches[7]) ? str_replace(',', '', $matches[7]) : null
    //             ];
    //         }
    //     }
    
    //     return $data;
    // }
    
    
    

    
    public function import(Request $request)
    {
        // Validate the uploaded PDF
        $request->validate([
            'pdf' => 'required|file|mimes:pdf',
        ]);

        // Path to the uploaded PDF
        $pdfPath = $request->file('pdf')->getRealPath();

        // Extract text from the PDF
        $text = Pdf::getText($pdfPath);

        // Process the extracted text and convert it into structured data
        // In this case, we'll split by new lines and handle each line as a transaction
        $lines = explode("\n", $text);
        $transactions = [];

        foreach ($lines as $line) {
            // Parsing logic - this should be customized based on your PDF structure
            $parts = preg_split('/\s+/', trim($line));

            // Skip invalid lines or headers
            if (count($parts) < 6 || !is_numeric($parts[0])) {
                continue;
            }

            // Sample structure for each transaction
            $transactionData = [
                'transaction_date'        => $parts[0], // Assuming the first part is the date
                'narration' => $parts[1], // Particulars may be a string
                'chq_ref_no'  => $parts[2], // Check reference number
                'withdrawl'  => is_numeric($parts[3]) ? $parts[3] : null, // Withdrawal amount
                'deposit'     => is_numeric($parts[4]) ? $parts[4] : null, // Deposit amount
                'balance'     => is_numeric($parts[5]) ? $parts[5] : null, // Balance
            ];

            // Store the data in the database
            BankReconciliation::create($transactionData);

            // Push to the array for JSON response
            $transactions[] = $transactionData;
        }

        // Return the transactions as JSON
        return response()->json($transactions);
    }


    // public function import(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,xls,pdf'
    //     ]);

    //     $file = $request->file('file');
    //     $extension = $file->getClientOriginalExtension();

    //     // Handle Excel files
    //     if (in_array($extension, ['xlsx', 'xls'])) {
    //         $spreadsheet = IOFactory::load($file->getRealPath());
    //         $sheet = $spreadsheet->getActiveSheet();

    //         foreach ($sheet->getRowIterator() as $rowIndex => $row) {
    //             if ($rowIndex === 1) continue; // Skip header row

    //             $rowData = [];
    //             foreach ($row->getCellIterator() as $cell) {
    //                 $rowData[] = $cell->getValue();
    //             }

    //             $transactionDate = $rowData[0] ?? null;
    //             if (is_numeric($transactionDate)) {
    //                 $transactionDate = Date::excelToDateTimeObject($transactionDate)->format('Y-m-d');
    //             }

    //             $withdrawl = $rowData[3] ?? null;
    //             $deposit = $rowData[4] ?? null;
    //             $transactionType = null;

    //             if (!empty($withdrawl) && is_numeric($withdrawl)) {
    //                 $transactionType = 'Payment';
    //             } elseif (!empty($deposit) && is_numeric($deposit)) {
    //                 $transactionType = 'Receipt';
    //             }

    //             BankReconciliation::create([
    //                 'transaction_date' => $transactionDate,
    //                 'narration' => $rowData[1] ?? null,
    //                 'chq_ref_no' => $rowData[2] ?? null,
    //                 'withdrawl' => $withdrawl,
    //                 'deposit' => $deposit,
    //                 'balance' => $rowData[5] ?? null,
    //                 'transaction_type' => $transactionType,
    //             ]);
    //         }

    //         return redirect()->back()->with('success', 'Bank statements imported successfully!');
    //     }

    //     // Handle PDF files
    //     if ($extension == 'pdf') {
    //         $apiKey = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJiYW5rc3RhdGVtZW50Y29udmVydGVyLmNvbSIsInVzZXJJZCI6MzU1MTEsImVtYWlsIjoidGFsbHljb25uZWN0c2RldmVsb3BlckBnbWFpbC5jb20ifQ.PnVbGonFe7sGegdXt_Qr3df_1_p9twWDYJCNnSl9Kto'; // Replace with your actual API key

    //         // Step 1: Upload PDF to API
    //         try {
    //             $uploadResponse = Http::withHeaders([
    //                 'Authorization' => 'Bearer ' . $apiKey,
    //             ])->attach(
    //                 'file', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
    //             )->post('https://api2.bankstatementconverter.com/api/v1/BankStatement');

    //             if ($uploadResponse->failed()) {
    //                 Log::error('PDF upload failed: ' . $uploadResponse->body());
    //                 return redirect()->back()->with('error', 'Failed to upload PDF. Please try again later.');
    //             }

    //             $uploadData = $uploadResponse->json();
    //             $uuids = array_column($uploadData, 'uuid'); // Extract UUIDs from the response

    //             // Step 2: Convert PDF using UUIDs
    //             $convertResponse = Http::withHeaders([
    //                 'Authorization' => 'Bearer ' . $apiKey,
    //             ])->post('https://api2.bankstatementconverter.com/api/v1/BankStatement/convert?format=JSON', [
    //                 'uuids' => $uuids
    //             ]);

    //             if ($convertResponse->failed()) {
    //                 Log::error('PDF conversion failed: ' . $convertResponse->body());
    //                 return redirect()->back()->with('error', 'Failed to convert PDF. Please try again later.');
    //             }

    //             $jsonData = $convertResponse->json();
    //             $data = $jsonData; // Assuming the structure provided in the example
                
    //         } catch (\Exception $e) {
    //             Log::error('Error during PDF processing: ' . $e->getMessage());
    //             return redirect()->back()->with('error', 'An error occurred while processing the PDF. Please try again later.');
    //         }

    //         // Step 3: Process and store the converted data
    //         foreach ($data as $doc) {
    //             foreach ($doc['normalised'] as $rowData) {
    //                 $transactionDate = $rowData['date'] ?? null;
    //                 $amount = $rowData['amount'] ?? null;

    //                 // Assuming that the amount is negative for withdrawals and positive for deposits
    //                 $withdrawl = $amount < 0 ? abs($amount) : null;
    //                 $deposit = $amount >= 0 ? $amount : null;
    //                 $transactionType = $withdrawl ? 'Payment' : 'Receipt';

    //                 BankReconciliation::create([
    //                     'transaction_date' => Carbon::createFromFormat('d/m/Y', $transactionDate)->format('Y-m-d'),
    //                     'narration' => $rowData['description'] ?? null,
    //                     'withdrawl' => $withdrawl,
    //                     'deposit' => $deposit,
    //                     'balance' => null, // Balance might not be available from the normalised data
    //                     'transaction_type' => $transactionType,
    //                 ]);
    //             }
    //         }

    //         return redirect()->back()->with('success', 'Bank statements imported successfully!');
    //     }
    // }


    // public function import(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,xls,pdf'
    //     ]);

    //     $file = $request->file('file');
    //     $extension = $file->getClientOriginalExtension();
    //     $data = [];

    //     if (in_array($extension, ['xlsx', 'xls'])) {
    //         // Process Excel file
    //         $spreadsheet = IOFactory::load($file->getRealPath());
    //         $sheet = $spreadsheet->getActiveSheet();

    //         foreach ($sheet->getRowIterator() as $rowIndex => $row) {
    //             if ($rowIndex === 1) continue; // Skip header row

    //             $rowData = [];
    //             foreach ($row->getCellIterator() as $cell) {
    //                 $rowData[] = $cell->getValue();
    //             }

    //             $data[] = $rowData; // Store all rows
    //         }
    //     } elseif ($extension == 'pdf') {
    //         // Convert PDF to JSON using Bank Statement Converter API
    //         $apiKey = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJiYW5rc3RhdGVtZW50Y29udmVydGVyLmNvbSIsInVzZXJJZCI6MzU0OTksImVtYWlsIjoic3VucGFsbGF2aTIwMDlAZ21haWwuY29tIn0.UDngbs20ieJaymHxZZ35pBQFkqbuUPqao2by7LrpHBA';
    //         try {
    //             $response = Http::withHeaders([
    //                 'Authorization' => 'Bearer ' . $apiKey,
    //             ])->attach(
    //                 'file', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
    //             )->post('https://api2.bankstatementconverter.com/api/v1/BankStatement/convert?format=JSON', [
    //                 'format' => 'json',
    //             ]);
            
    //             if ($response->failed()) {
    //                 Log::error('PDF conversion failed: ' . $response->body());
    //                 return redirect()->back()->with('error', 'Failed to convert PDF. Please try again later.');
    //             }
            
    //             $jsonData = $response->json();
    //             $data = $jsonData['transactions'] ?? []; // Adjust based on the actual JSON structure
    //         } catch (\Exception $e) {
    //             Log::error('Error during PDF conversion: ' . $e->getMessage());
    //             return redirect()->back()->with('error', 'An error occurred while converting the PDF. Please try again later.');
    //         }
            
    //     }

    //     // Process imported data
    //     foreach ($data as $rowData) {
    //         $transactionDate = $rowData['transaction_date'] ?? null;
    //         if ($this->isValidDate($transactionDate)) {
    //             $transactionDate = Carbon::createFromFormat('Y-m-d', $transactionDate)->format('Y-m-d');
    //         } else {
    //             $transactionDate = null; // Set to null or handle invalid date
    //         }

    //         $withdrawl = $rowData['withdrawl'] ?? null;
    //         $deposit = $rowData['deposit'] ?? null;
    //         $transactionType = null;

    //         if (!empty($withdrawl) && is_numeric($withdrawl)) {
    //             $transactionType = 'Payment';
    //         } elseif (!empty($deposit) && is_numeric($deposit)) {
    //             $transactionType = 'Receipt';
    //         }

    //         BankReconciliation::create([
    //             'transaction_date' => $transactionDate,
    //             'narration' => $rowData['narration'] ?? null,
    //             'chq_ref_no' => $rowData['chq_ref_no'] ?? null,
    //             'withdrawl' => $withdrawl,
    //             'deposit' => $deposit,
    //             'balance' => $rowData['balance'] ?? null,
    //             'transaction_type' => $transactionType,
    //         ]);
    //     }

    //     return redirect()->back()->with('success', 'Bank statements imported successfully!');
    // }

    // public function import(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,xls,pdf'
    //     ]);
    
    //     $file = $request->file('file');
    //     $extension = $file->getClientOriginalExtension();
    //     $data = [];
    
    //     if (in_array($extension, ['xlsx', 'xls'])) {
    //         // Process Excel file
    //         $spreadsheet = IOFactory::load($file->getRealPath());
    //         $sheet = $spreadsheet->getActiveSheet();
    
    //         foreach ($sheet->getRowIterator() as $rowIndex => $row) {
    //             if ($rowIndex === 1) continue; // Skip header row
    
    //             $rowData = [];
    //             foreach ($row->getCellIterator() as $cell) {
    //                 $rowData[] = $cell->getValue();
    //             }
    
    //             $data[] = $rowData; // Store all rows
    //         }
    //     } elseif ($extension == 'pdf') {
    //         // Convert PDF to JSON using Bank Statement Converter API
    //         $apiKey = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJiYW5rc3RhdGVtZW50Y29udmVydGVyLmNvbSIsInVzZXJJZCI6MzU1MTEsImVtYWlsIjoidGFsbHljb25uZWN0c2RldmVsb3BlckBnbWFpbC5jb20ifQ.PnVbGonFe7sGegdXt_Qr3df_1_p9twWDYJCNnSl9Kto';
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $apiKey,
    //         ])->attach(
    //             'file', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
    //         )->post('https://api.bankstatementconverter.com/convert', [
    //             'format' => 'json',
    //         ]);
    
    //         if ($response->failed()) {
    //             Log::error('PDF conversion failed: ' . $response->body());
    //             return redirect()->back()->with('error', 'Failed to convert PDF. Please try again later.');
    //         }
    
    //         $jsonData = $response->json();
    //         $data = $jsonData['transactions'] ?? []; // Adjust based on the actual JSON structure
    //     }
    
    //     // Process imported data
    //     foreach ($data as $rowData) {
    //         $transactionDate = $rowData['transaction_date'] ?? null;
    //         if (is_numeric($transactionDate)) {
    //             $transactionDate = Date::excelToDateTimeObject($transactionDate)->format('Y-m-d');
    //         }
    
    //         $withdrawl = $rowData['withdrawl'] ?? null;
    //         $deposit = $rowData['deposit'] ?? null;
    //         $transactionType = null;
    
    //         if (!empty($withdrawl) && is_numeric($withdrawl)) {
    //             $transactionType = 'Payment';
    //         } elseif (!empty($deposit) && is_numeric($deposit)) {
    //             $transactionType = 'Receipt';
    //         }
    
    //         BankReconciliation::create([
    //             'transaction_date' => $transactionDate,
    //             'narration' => $rowData['narration'] ?? null,
    //             'chq_ref_no' => $rowData['chq_ref_no'] ?? null,
    //             'withdrawl' => $withdrawl,
    //             'deposit' => $deposit,
    //             'balance' => $rowData['balance'] ?? null,
    //             'transaction_type' => $transactionType,
    //         ]);
    //     }
    
    //     return redirect()->back()->with('success', 'Bank statements imported successfully!');
    // }
    


    // public function import(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,xls,pdf'
    //     ]);
    
    //     $file = $request->file('file');
    //     $extension = $file->getClientOriginalExtension();
    
    //     $data = [];
    
    //     if (in_array($extension, ['xlsx', 'xls'])) {
    //         // Process Excel file
    //         $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
    //         $sheet = $spreadsheet->getActiveSheet();
    
    //         foreach ($sheet->getRowIterator() as $rowIndex => $row) {
    //             if ($rowIndex === 1) continue; // Skip header row
    
    //             $rowData = [];
    //             foreach ($row->getCellIterator() as $cell) {
    //                 $rowData[] = $cell->getValue();
    //             }
    
    //             $data[] = $rowData; // Store all rows
    //         }
    //     } elseif ($extension == 'pdf') {
    //         // Move the file to storage and get the path
    //         $pdfPath = $file->storeAs('app', $file->getClientOriginalName()); // Save file in storage/app
    //         $pdfPath = storage_path('app/' . $file->getClientOriginalName()); // Get the full path
    
    //         // Extract text using Smalot PDF Parser
    //         try {
    //             $parser = new \Smalot\PdfParser\Parser();
    //             $pdf = $parser->parseFile($pdfPath);
    //             $text = $pdf->getText();
    //         } catch (\Exception $e) {
    //             Log::error("Error extracting text from PDF: " . $e->getMessage());
    //             return redirect()->back()->with('error', 'Failed to extract text from PDF.');
    //         }
    
    //         // Parse PDF content
    //         $rows = explode("\n", $text);
    //         foreach ($rows as $row) {
    //             $rowData = preg_split('/\s+/', trim($row)); // Split by spaces or other logic to split the rows
    //             $data[] = $rowData;
    //         }
    //     }
    
    //     // Process imported data
    //     foreach ($data as $rowData) {
    //         $transactionDate = $rowData[0] ?? null;
            
    //         // Validate and format date
    //         if ($this->isValidDate($transactionDate)) {
    //             $transactionDate = Carbon::createFromFormat('Y-m-d', $transactionDate)->format('Y-m-d');
    //         } else {
    //             $transactionDate = null; // Set to null or handle invalid date
    //         }
    
    //         $withdrawl = $rowData[3] ?? null;
    //         $deposit = $rowData[4] ?? null;
    //         $transactionType = null;
    
    //         if (!empty($withdrawl) && is_numeric($withdrawl)) {
    //             $transactionType = 'Payment';
    //         } elseif (!empty($deposit) && is_numeric($deposit)) {
    //             $transactionType = 'Receipt';
    //         }
    
    //         BankReconciliation::create([
    //             'transaction_date' => $transactionDate,
    //             'narration' => $rowData[1] ?? null,
    //             'chq_ref_no' => $rowData[2] ?? null,
    //             'withdrawl' => $withdrawl,
    //             'deposit' => $deposit,
    //             'balance' => $rowData[5] ?? null,
    //             'transaction_type' => $transactionType,
    //         ]);
    //     }
    
    //     return redirect()->back()->with('success', 'Bank statements imported successfully!');
    // }
    
    
    // protected function isValidDate($date)
    // {
    //     try {
    //         $parsedDate = Carbon::parse($date);
    //         return $parsedDate->format('Y-m-d') === $date;
    //     } catch (\Exception $e) {
    //         return false;
    //     }
    // }
    


    // public function import(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,xls'
    //     ]);

    //     $file = $request->file('file');
    //     $spreadsheet = IOFactory::load($file->getRealPath());
    //     $sheet = $spreadsheet->getActiveSheet();

    //     foreach ($sheet->getRowIterator() as $rowIndex => $row) {
    //         if ($rowIndex === 1) continue;

    //         $rowData = [];
    //         foreach ($row->getCellIterator() as $cell) {
    //             $rowData[] = $cell->getValue();
    //         }

    //         $transactionDate = $rowData[0] ?? null;
    //         if (is_numeric($transactionDate)) {
    //             $transactionDate = Date::excelToDateTimeObject($transactionDate)->format('Y-m-d');
    //         }

    //         $withdrawl = $rowData[3] ?? null;
    //         $deposit = $rowData[4] ?? null;
    //         $transactionType = null;

    //         if (!empty($withdrawl) && is_numeric($withdrawl)) {
    //             $transactionType = 'Payment';
    //         } elseif (!empty($deposit) && is_numeric($deposit)) {
    //             $transactionType = 'Receipt';
    //         }

    //         BankReconciliation::create([
    //             'transaction_date' => $transactionDate,
    //             'narration' => $rowData[1] ?? null,
    //             'chq_ref_no' => $rowData[2] ?? null,
    //             'withdrawl' => $withdrawl,
    //             'deposit' => $deposit,
    //             'balance' => $rowData[5] ?? null,
    //             'transaction_type' => $transactionType,
    //         ]);
    //     }

    //     return redirect()->back()->with('success', 'Bank statements imported successfully!');
    // }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $query = BankReconciliation::all();

            return DataTables::of($query)
                ->addIndexColumn()
                ->make(true);
        }
    }
    
}