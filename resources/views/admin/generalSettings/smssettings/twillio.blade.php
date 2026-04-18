@extends('layouts.admin')

@php
    $pageEyebrow = 'SMS gateway';
    $pageTitle = 'Twilio Configuration';
    $pageSubtitle = 'Configure Twilio credentials for OTP delivery and keep the sending number aligned with your verified Twilio account.';
    $providerKey = 'twillio';
    $providerLabel = trans('global.twillio');
    $formAction = route('admin.updatetwillio');
    $cardTitle = 'Twilio credentials';
    $cardSubtitle = 'Save the verified sender number, account SID, and auth token used for SMS delivery.';
    $callout = [
        'text' => 'Twilio rejects SMS from unverified or unprovisioned numbers. Make sure the sender number below exists in the same account as the SID and auth token.',
    ];
    $fields = [
        [
            'label' => trans('global.number'),
            'name' => 'twillio_number',
            'type' => 'text',
            'placeholder' => 'Twilio sender number',
            'value' => $twillio_number->meta_value ?? '',
        ],
        [
            'label' => trans('global.sid'),
            'name' => 'twillio_key',
            'type' => 'password',
            'placeholder' => 'Account SID',
            'value' => $twillio_key->meta_value ?? '',
        ],
        [
            'label' => trans('global.token'),
            'name' => 'twillio_secret',
            'type' => 'password',
            'placeholder' => 'Auth token',
            'value' => $twillio_secret->meta_value ?? '',
            'full' => true,
        ],
    ];
@endphp

@section('content')
    @include('admin.generalSettings.smssettings.provider-form')
@endsection

@section('scripts')
    @include('admin.generalSettings.smssettings.toastrmsg')
@endsection
