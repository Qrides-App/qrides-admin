@extends('layouts.admin')

@php
    $pageEyebrow = 'SMS gateway';
    $pageTitle = 'Nexmo Configuration';
    $pageSubtitle = 'Keep legacy Nexmo credentials available for older tenants or integrations that still rely on the original Vonage-based SMS setup.';
    $providerKey = 'nexmo';
    $providerLabel = trans('global.nexmo');
    $formAction = route('admin.updatenexmosetting');
    $cardTitle = 'Nexmo credentials';
    $cardSubtitle = 'Save the key and secret pair used to authenticate SMS requests through the legacy Nexmo gateway.';
    $callout = [
        'text' => 'If you are no longer using Nexmo, leave the credentials stored but keep another provider active. This page exists mainly for backward compatibility.',
    ];
    $fields = [
        [
            'label' => trans('global.key'),
            'name' => 'nexmo_key',
            'type' => 'password',
            'placeholder' => 'Nexmo key',
            'value' => $nexmo_key->meta_value ?? '',
        ],
        [
            'label' => trans('global.secrete'),
            'name' => 'nexmo_secret',
            'type' => 'password',
            'placeholder' => 'Nexmo secret',
            'value' => $nexmo_secret->meta_value ?? '',
        ],
    ];
@endphp

@section('content')
    @include('admin.generalSettings.smssettings.provider-form')
@endsection

@section('scripts')
    @include('admin.generalSettings.smssettings.toastrmsg')
@endsection
