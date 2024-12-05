<?php

namespace App\Http\Controllers\DesktopApp\Import;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterImportRequest;
use App\Services\Import\TallyMasterService;
use Illuminate\Http\Request;

class MasterImportController extends Controller
{

    protected $tallyMasterService;

    public function __construct(TallyMasterService $tallyMasterService)
    {
        $this->tallyMasterService = $tallyMasterService;
    }

    public function import(MasterImportRequest $request)
    {
        $response = $this->tallyMasterService->importMasterJson($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            return response()->json($data, $response->status());
        }

        return $response;
    }

}
