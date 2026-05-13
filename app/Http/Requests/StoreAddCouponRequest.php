<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddCouponRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('add_coupon_create');
    }

    public function rules()
    {
        return [
            'coupon_title' => [
                'string',
                'required',
                'max:255',
            ],
            'coupon_subtitle' => [
                'string',
                'nullable',
                'max:255',
            ],
            'coupon_image_file' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],
            'coupon_description' => [
                'nullable',
                'string',
            ],
            'coupon_expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'coupon_code' => [
                'string',
                'required',
                'max:50',
                Rule::unique('add_coupons', 'coupon_code')->whereNull('deleted_at'),
            ],
            'coupon_type' => [
                'required',
                Rule::in(['percentage']),
            ],
            'coupon_value' => [
                'required',
                'numeric',
                'min:0',
                'max:99',
            ],
            'min_order_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'max_uses' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'max_uses_per_user' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'status' => [
                'required',
                Rule::in(array_keys(\App\Models\AddCoupon::STATUS_SELECT)),
            ],
        ];
    }
}
