@extends('layouts.admin')

@php
    $pageEyebrow = 'SMS gateway';
    $pageTitle = 'Sinch Configuration';
    $pageSubtitle = 'Keep the Sinch service plan and token in sync with the sender number used for login and transactional SMS delivery.';
    $providerKey = 'sinch';
    $providerLabel = 'Sinch';
    $formAction = route('admin.updateSinch');
    $cardTitle = 'Sinch credentials';
    $cardSubtitle = 'Store the service plan, API token, and sender number used by your Sinch messaging integration.';
    $callout = [
        'text' => 'Sinch typically expects the sender number to be provisioned inside the same service plan. If OTPs stop arriving, confirm both records together.',
    ];
    $fields = [
        [
            'label' => trans('global.service_plan_id'),
            'name' => 'sinch_service_plan_id',
            'type' => 'password',
            'placeholder' => 'Service plan ID',
            'value' => $sinch_service_plan_id->meta_value ?? '',
        ],
        [
            'label' => trans('global.api_token'),
            'name' => 'sinch_api_token',
            'type' => 'password',
            'placeholder' => 'API token',
            'value' => $sinch_api_token->meta_value ?? '',
        ],
        [
            'label' => trans('global.sender_number'),
            'name' => 'sinch_sender_number',
            'type' => 'text',
            'placeholder' => 'Sender number',
            'value' => $sinch_sender_number->meta_value ?? '',
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
