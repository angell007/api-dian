<?php

namespace App\Http\Requests\Api;

use App\Rules\ResolutionSetting;
use Illuminate\Foundation\Http\FormRequest;

class ImplicitAcceptancRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->resolution = auth()->user()->company->resolutions->where('type_document_id', $this->type_document_id)->first();
       
       
        return [
            // Document
            'type_document_id' => [
                'required',
                'in:13',
                'exists:type_documents,id',
                new ResolutionSetting(),
            ],

            // Date time
            'date' => 'nullable|date_format:Y-m-d',
            'time' => 'nullable|date_format:H:i:s',
            

            // Consecutive
            'number' => 'required|integer|between:'.optional($this->resolution)->from.','.optional($this->resolution)->to,
            'code' => 'required|string',
            'prefix' => 'required|string',
            

            // Customer
            'supplier' => 'required|array',
            'supplier.identification_number' => 'required|numeric|digits_between:1,15',
            'supplier.dv' => 'nullable|numeric|digits:1',
            'supplier.type_document_identification_id' => 'nullable|exists:type_document_identifications,id',
            'supplier.type_regime_id' => 'nullable|exists:type_regimes,id',
            'supplier.tax_id' => 'nullable|exists:taxes,id',
           


            'person.identification_number' => 'required|numeric|digits_between:1,15',
            'person.type_document_identification_id' => 'nullable|exists:type_document_identifications,id',
            'person.dv' => 'nullable|numeric|digits:1',
            'person.firstName' => 'required|string',
            'person.familyName' => 'required|string',
            'person.jobTitle' => 'required|string',
            'person.organizationDepartment' => 'required|string'
          
          
      
        ];
    }
}
