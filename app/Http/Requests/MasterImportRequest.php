<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterImportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'license_number' => 'required|string',
            // 'uploadFile' => 'nullable|file|mimes:json',
            // Other validation rules...
        ];
    }
}
