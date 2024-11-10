<?php

namespace App\Services;

use App\Repositories\Contracts\TallyCompanyRepositoryInterface;
use Illuminate\Support\Facades\Log;

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

    // Private methods for data extraction and processing
}
