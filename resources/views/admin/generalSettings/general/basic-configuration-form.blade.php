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
                        <span class="settings-page-header__eyebrow">General settings</span>
                        <h1 class="settings-page-header__title">Branding and contact settings</h1>
                        <p class="settings-page-header__subtitle">Update the QRIDES admin name, support contact details, GST identity, default language, logo, and favicon used across the platform.</p>
                    </div>
                </div>

                <form id="general_form" method="POST" action="{{ route('admin.add_configuration_wizard') }}"
                    class="settings-card settings-form-card" enctype="multipart/form-data" novalidate="novalidate">
                    @csrf
                    <input type="hidden" name="general_default_country_code" id="general_default_country_code"
                        value="{{ old('general_default_country_code', '') }}">

                    <div class="settings-card__header">
                        <div>
                            <h3>Business identity</h3>
                            <p>These values appear in admin branding, login screens, support contact references, and shared platform metadata.</p>
                        </div>
                    </div>

                    <div class="settings-callout settings-callout--soft">
                        <i class="fa fa-info-circle"></i>
                        <span>Currency management is now handled in the dedicated Currency Settings page. Use this screen for brand identity and contact defaults only.</span>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label for="general_name">Brand name <span class="text-danger">*</span></label>
                            <input type="text" name="general_name" class="form-control" id="general_name"
                                value="{{ old('general_name', $general_name->meta_value ?? '') }}" placeholder="QRIDES">
                        </div>

                        <div class="settings-field">
                            <label for="general_email">Support email <span class="text-danger">*</span></label>
                            <input type="email" name="general_email" class="form-control" id="general_email"
                                value="{{ old('general_email', $general_email->meta_value ?? '') }}" placeholder="Support email">
                        </div>

                        <div class="settings-field settings-field--full">
                            <label for="general_description">Site description</label>
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
                            <label for="general_gstin">GSTIN</label>
                            <input class="form-control" type="text" name="general_gstin" id="general_gstin"
                                value="{{ old('general_gstin', $general_gstin->meta_value ?? '') }}"
                                placeholder="22AAAAA0000A1Z5">
                        </div>

                        <div class="settings-field">
                            <label for="general_upi_id">Platform UPI ID</label>
                            <input class="form-control" type="text" name="general_upi_id" id="general_upi_id"
                                value="{{ old('general_upi_id', $general_upi_id->meta_value ?? '') }}"
                                placeholder="payments@upi">
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
                            <label for="general_logo">Brand logo</label>
                            <input type="file" name="general_logo" class="form-control" id="general_logo" accept="image/*">
                            <div class="settings-media-preview-wrap">
                                <div class="settings-media-preview-copy">Current logo</div>
                                <div class="settings-media-preview" id="general_logo_preview">
                                    @if (!empty($generalLogoPreviewUrl))
                                        <img src="{{ $generalLogoPreviewUrl }}" alt="Current logo">
                                    @else
                                        <span>No logo uploaded</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="settings-field">
                            <label for="general_favicon">Site favicon</label>
                            <input type="file" name="general_favicon" class="form-control" id="general_favicon" accept="image/*">
                            <div class="settings-media-preview-wrap">
                                <div class="settings-media-preview-copy">Current favicon</div>
                                <div class="settings-media-preview settings-media-preview--favicon" id="general_favicon_preview">
                                    @if (!empty($generalFaviconPreviewUrl))
                                        <img src="{{ $generalFaviconPreviewUrl }}" alt="Current favicon">
                                    @else
                                        <span>No favicon uploaded</span>
                                    @endif
                                </div>
                            </div>
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
            setupImagePreview('general_logo', 'general_logo_preview', 'Selected logo preview');
            setupImagePreview('general_favicon', 'general_favicon_preview', 'Selected favicon preview');
        });

        function setupImagePreview(inputId, previewId, label) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);

            if (!input || !preview) {
                return;
            }

            input.addEventListener('change', function(event) {
                const [file] = event.target.files || [];
                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="${label}">`;
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
@endsection
