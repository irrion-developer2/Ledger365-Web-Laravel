<?php

namespace App\Http\Controllers\DesktopApp\Import;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyImportRequest;
use App\Services\Import\TallyCompanyService;
use Illuminate\Http\Request;

class CompanyImportController extends Controller
{

    protected $tallyCompanyService;

    public function __construct(TallyCompanyService $tallyCompanyService)
    {
        $this->tallyCompanyService = $tallyCompanyService;
    }

    public function import(CompanyImportRequest $request)
    {
        $response = $this->tallyCompanyService->importCompanyJson($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true); // true to get the data as an associative array
            return response()->json($data, $response->status());
        }

        return $response;
    }

}
