<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\TallyCompanyRepositoryInterface;

class TallyCompanyRepository implements TallyCompanyRepositoryInterface
{
    public function saveCompanyData(array $data)
    {
        // Use Eloquent to save company data
        return TallyCompany::updateOrCreate(
            ['company_guid' => $data['company_guid']],
            $data
        );
    }

    public function findByGuid(string $guid)
    {
        return TallyCompany::where('company_guid', $guid)->first();
    }
}
