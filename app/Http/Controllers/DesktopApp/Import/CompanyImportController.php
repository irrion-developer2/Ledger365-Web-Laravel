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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
