@extends('layouts.admin')

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-3 settings_bar_gap">
                <div class="box box-info box_info settings-sidebar-card">
                    <h4 class="all_settings f-18 mt-1">Manage Settings</h4>
                    @include('admin.generalSettings.general-setting-links.links')
                </div>
            </div>

            <div class="col-md-9">
                <div class="settings-page-header">
                    <div>
                        <span class="settings-page-header__eyebrow">Brand & identity</span>
                        <h1 class="settings-page-header__title">{{ trans('global.general_title_singular') }}</h1>
                        <p class="settings-page-header__subtitle">Manage your admin brand identity, contact information, primary locale, and the assets shown across login, mail, and dashboard surfaces.</p>
                    </div>
                </div>

                <form id="general_form" method="POST" action="{{ route('admin.add_configuration_wizard') }}"
                    class="settings-card settings-form-card" enctype="multipart/form-data" novalidate="novalidate">
                    @csrf
                    <input type="hidden" name="general_default_country_code" id="general_default_country_code"
                        value="{{ old('general_default_country_code', '') }}">

                    <div class="settings-card__header">
                        <div>
                            <h3>Core business profile</h3>
                            <p>These details are used across admin, transactional mail, login branding, and default communication settings.</p>
                        </div>
                    </div>

                    <div class="settings-callout settings-callout--soft">
                        <i class="fa fa-info-circle"></i>
                        <span>Currency management is now handled in the dedicated Currency Settings page. Use this screen for brand identity and contact defaults only.</span>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label for="general_name">{{ trans('global.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="general_name" class="form-control" id="general_name"
                                value="{{ old('general_name', $general_name->meta_value ?? '') }}" placeholder="Business name">
                        </div>

                        <div class="settings-field">
                            <label for="general_email">{{ trans('global.email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="general_email" class="form-control" id="general_email"
                                value="{{ old('general_email', $general_email->meta_value ?? '') }}" placeholder="Support email">
                        </div>

                        <div class="settings-field settings-field--full">
                            <label for="general_description">{{ trans('global.site_desciption') }}</label>
                            <input type="text" name="general_description" class="form-control" id="general_description"
                                value="{{ old('general_description', $general_description->meta_value ?? '') }}"
                                placeholder="Short description used in admin and browser metadata">
                        </div>

                        <div class="settings-field">
                            <label for="general_default_phone_country">{{ trans('global.phone_country') }} <span class="text-danger">*</span></label>
                            <select class="form-control" name="general_default_phone_country" id="general_default_phone_country"
                                onchange="updateDefaultCountry()">
                                @foreach (config('countries') as $country)
                                    <option value="{{ $country['dial_code'] }}"
                                        data-country-code="{{ $country['code'] }}"
                                        {{ old('general_default_phone_country', $general_default_phone_country->meta_value ?? '') == $country['dial_code'] ? 'selected' : '' }}>
                                        {{ $country['name'] }} ({{ $country['dial_code'] }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->has('general_default_phone_country'))
                                <span class="help-block text-danger">{{ $errors->first('general_default_phone_country') }}</span>
                            @endif
                        </div>

                        <div class="settings-field">
                            <label for="general_phone">Phone Number <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="general_phone" id="general_phone"
                                value="{{ old('general_phone', $general_phone->meta_value ?? '') }}">
                            @if ($errors->has('general_phone'))
                                <span class="help-block text-danger">{{ $errors->first('general_phone') }}</span>
                            @endif
                        </div>

                        <div class="settings-field">
                            <label for="default_language">{{ trans('global.default_language') }}</label>
                            <select class="form-control" id="default_language" name="general_default_language">
                                @foreach ($languagedata as $language)
                                    <option value="{{ $language->short_name }}"
                                        {{ old('general_default_language', $general_default_language->meta_value ?? null) == $language->short_name ? 'selected' : '' }}>
                                        {{ $language->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="settings-field">
                            <label for="general_logo">{{ trans('global.logo') }}</label>
                            <input type="file" name="general_logo" class="form-control" id="general_logo" accept="image/*">
                            @if (!empty($general_logo->meta_value))
                                <div class="settings-media-preview">
                                    <img src="{{ asset('storage/' . $general_logo->meta_value) }}" alt="Current logo">
                                </div>
                            @endif
                        </div>

                        <div class="settings-field">
                            <label for="general_favicon">{{ trans('global.favicon') }}</label>
                            <input type="file" name="general_favicon" class="form-control" id="general_favicon" accept="image/*">
                            @if (!empty($general_favicon->meta_value))
                                <div class="settings-media-preview settings-media-preview--favicon">
                                    <img src="{{ asset('storage/' . $general_favicon->meta_value) }}" alt="Current favicon">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="settings-actions">
                        <a class="btn settings-btn-secondary" href="{{ route('admin.settings') }}">{{ trans('global.cancel') }}</a>
                        <button type="submit" class="btn btn-primary settings-btn-primary">{{ trans('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        function updateDefaultCountry() {
            const phoneCountryDropdown = document.getElementById('general_default_phone_country');
            const defaultCountryField = document.getElementById('general_default_country_code');
            const selectedDialCode = phoneCountryDropdown.value;
            const selectedOption = phoneCountryDropdown.querySelector(`option[value="${selectedDialCode}"]`);
            const countryCode = selectedOption ? selectedOption.getAttribute('data-country-code') : '';
            defaultCountryField.value = countryCode;
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateDefaultCountry();
        });
    </script>
@endsection
