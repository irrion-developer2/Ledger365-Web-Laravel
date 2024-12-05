<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Repositories\Contracts\TallyCompanyRepositoryInterface;

class TallyLicenseService
{
    protected $tallyCompanyRepository;

    public function __construct(TallyCompanyRepositoryInterface $tallyCompanyRepository)
    {
        $this->tallyCompanyRepository = $tallyCompanyRepository;
    }

    public function importCompanyJson($request)
    {
        // Extract JSON data from request
        $jsonData = $this->extractJsonData($request);

        // Process JSON data
        $companyData = $this->processJsonData($jsonData);

        // Save to repository
        $this->tallyCompanyRepository->saveCompanyData($companyData);

        // Additional processing if needed
    }


}
