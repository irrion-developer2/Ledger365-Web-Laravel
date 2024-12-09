<?php

namespace App\Http\Controllers\DesktopApp\Import;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterImportRequest;
use App\Services\Import\TallyVoucherService;

class VoucherImportController extends Controller
{

    protected $tallyVoucherService;

    public function __construct(TallyVoucherService $tallyVoucherService)
    {
        $this->tallyVoucherService = $tallyVoucherService;
    }

    public function import(MasterImportRequest $request)
    {
        $response = $this->tallyVoucherService->importVoucherJson($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            return response()->json($data, $response->status());
        }

        return $response;
    }

}
