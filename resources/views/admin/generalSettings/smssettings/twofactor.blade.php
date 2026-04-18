@extends('layouts.admin')

@php
    $pageEyebrow = 'SMS gateway';
    $pageTitle = '2Factor Configuration';
    $pageSubtitle = 'Use 2Factor when your OTP program depends on merchant-based authentication and a dedicated token for verification APIs.';
    $providerKey = 'twofactor';
    $providerLabel = trans('global.2_factor');
    $formAction = route('admin.updatetwofactor');
    $cardTitle = '2Factor credentials';
    $cardSubtitle = 'Store the account key, secret, merchant identifier, and authentication token used for outbound verification SMS.';
    $callout = [
        'text' => 'This gateway uses both merchant and authentication tokens. Keep them aligned with the same 2Factor project to avoid failed OTP requests.',
    ];
    $fields = [
        [
            'label' => trans('global.key'),
            'name' => 'twofactor_key',
            'type' => 'password',
            'placeholder' => 'Gateway key',
            'value' => $twofactor_key->meta_value ?? '',
        ],
        [
            'label' => trans('global.secrete'),
            'name' => 'twofactor_secret',
            'type' => 'password',
            'placeholder' => 'Gateway secret',
            'value' => $twofactor_secret->meta_value ?? '',
        ],
        [
            'label' => trans('global.merchant_id'),
            'name' => 'twofactor_merchant_id',
            'type' => 'text',
            'placeholder' => 'Merchant ID',
            'value' => $twofactor_merchant_id->meta_value ?? '',
        ],
        [
            'label' => trans('global.authentication_token'),
            'name' => 'twofactor_authentication_token',
            'type' => 'password',
            'placeholder' => 'Authentication token',
            'value' => $twofactor_authentication_token->meta_value ?? '',
        ],
    ];
@endphp

@section('content')
    @include('admin.generalSettings.smssettings.provider-form')
@endsection

@section('scripts')
    @include('admin.generalSettings.smssettings.toastrmsg')
@endsection
