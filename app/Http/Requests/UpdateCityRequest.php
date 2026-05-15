<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCityRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('city_edit');
    }

    public function rules()
    {
        return [
            'city_name' => [
                'string',
                'required',
            ],
            'country_code' => [
                'string',
                'required',
                'size:2',
            ],
            'latitude' => [
                'required',
            ],
            'longtitude' => [
                'required',
            ],
            'status' => [
                'required',
            ],
        ];
    }
}
