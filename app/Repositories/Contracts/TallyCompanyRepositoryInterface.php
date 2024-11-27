<?php
namespace App\Repositories\Contracts;

interface TallyCompanyRepositoryInterface
{
    public function saveCompanyData(array $data);
    public function findByGuid(string $guid);
}
