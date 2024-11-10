<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyImportRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust authorization logic as needed
    }

    public function rules()
    {
        return [
            'license_number' => 'required|string',
            'uploadFile' => 'nullable|file|mimes:json',
            // Other validation rules...
        ];
    }
}
