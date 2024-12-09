<?php

namespace App\Services;

use App\Models\TallyLicense;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TallyLicenseCheck
{

    public function validateLicenseNumber(Request $request)
    {
        $licenseNumber = $request->input('license_number');
        if (empty($licenseNumber)) {
            Log::info('license number is required');
            throw new \Exception('license number ');
        }

        $license = TallyLicense::where('license_number', $licenseNumber)->first();
        if (!$license) {
            Log::info('License not found for license number: ' . $licenseNumber);
            throw new \Exception('License not found');
        } elseif ($license->status != 1) {
            Log::info('License not active for license number: ' . $licenseNumber);
            throw new \Exception('License not active');
        }
    }

    public function findTallyMessage($jsonArray, $path = '')
    {
        foreach ($jsonArray as $key => $value) {
            $currentPath = $path ? $path . '.' . $key : $key;
            if ($key === 'TALLYMESSAGE') {
                return ['path' => $currentPath, 'value' => $value];
            }
            if (is_array($value)) {
                $result = $this->findTallyMessage($value, $currentPath);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        return null;
    }

    public function getPrimaryGroup($group, $groupsByName)
    {
        $visited = [];
        while ($group->parent) {
            if (isset($visited[$group->ledger_group_name])) {
                break;
            }
            $visited[$group->ledger_group_name] = true;

            if (!isset($groupsByName[$group->parent])) {
                break;
            }
            $group = $groupsByName[$group->parent];
        }
        return $group->ledger_group_name;
    }

    public function extractNumericValue($string)
    {
        if ($string) {
            $string = trim($string);
            if (preg_match('/-?\d+(\.\d+)?/', $string, $matches)) {
                return (float)$matches[0];
            }
        }
        return null;
    }

    public function ensureArray($data)
    {
        if (is_array($data)) {
            return $data;
        }

        return !empty($data) ? [$data] : [];
    }

    public function normalizeEntries($entries)
    {
        if (is_object($entries)) {
            $entries = (array)$entries;
        }

        if (is_array($entries)) {
            foreach ($entries as &$entry) {
                if (is_object($entry)) {
                    $entry = (array)$entry;
                }
            }
            return empty($entries) || isset($entries[0]) ? $entries : [$entries];
        }

        return [];
    }

    public function convertToDesiredDateFormat($date)
    {
        if (preg_match('/^\d{8}$/', $date)) {
            $dateObject = \DateTime::createFromFormat('Ymd', $date);
            return $dateObject ? $dateObject->format('d-M-y') : $date; // Example: '25-Aug-24'
        }
        return $date;
    }

}