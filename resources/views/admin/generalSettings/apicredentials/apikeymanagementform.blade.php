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
                        <span class="settings-page-header__eyebrow">Third-party APIs</span>
                        <h1 class="settings-page-header__title">{{ trans('global.apiCredentials_title_singular') }}</h1>
                        <p class="settings-page-header__subtitle">Keep maps and reCAPTCHA credentials in one clean panel, similar to your Zentobazaar admin setup.</p>
                    </div>
                </div>

                <form id="api_credentials" method="post" action="{{ route('admin.apiauthenticationadd') }}"
                    class="settings-card settings-form-card" enctype="multipart/form-data" novalidate="novalidate">
                    {{ csrf_field() }}

                    <div class="settings-card__header">
                        <div>
                            <h3>Map and verification credentials</h3>
                            <p>Use browser-safe map keys here. Keep secret credentials protected and restricted at provider level.</p>
                        </div>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field settings-field--full">
                            <label for="google_map_key">{{ trans('global.google_mao_browser') }} <span class="text-danger">*</span></label>
                            <input type="password" name="api_google_map_key" class="form-control" id="google_map_key"
                                value="{{ $api_google_map_key->meta_value ?? '' }}" placeholder="Google Maps browser key">
                        </div>

                        <div class="settings-field">
                            <label for="general_captcha">{{ trans('global.captcha') }} <span class="text-danger">*</span></label>
                            <select name="general_captcha" id="general_captcha" class="form-control">
                                <option value="yes" {{ $general_captcha && $general_captcha->meta_value == 'yes' ? 'selected' : '' }}>Yes</option>
                                <option value="no" {{ $general_captcha && $general_captcha->meta_value == 'no' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>

                        <div class="settings-field">
                            <label for="site_key">{{ trans('global.site_key') }} <span class="text-danger">*</span></label>
                            <input type="password" name="site_key" id="site_key" class="form-control"
                                value="{{ $site_key->meta_value ?? '' }}" placeholder="reCAPTCHA site key" required>
                        </div>

                        <div class="settings-field settings-field--full">
                            <label for="private_key">{{ trans('global.private_key') }} <span class="text-danger">*</span></label>
                            <input type="password" name="private_key" id="private_key" class="form-control"
                                value="{{ $private_key->meta_value ?? '' }}" placeholder="reCAPTCHA secret key" required>
                        </div>
                    </div>

                    <div class="settings-callout settings-callout--soft">
                        <i class="fa fa-info-circle"></i>
                        <span>Need new reCAPTCHA keys? Create them at <a href="https://www.google.com/recaptcha/admin/create" target="_blank">google.com/recaptcha/admin/create</a> and paste the site and secret key here.</span>
                    </div>

                    <div class="settings-card__footer">
                        <button type="submit" class="btn btn-primary btn-space">{{ trans('global.save') }}</button>
                        <a class="btn btn-default" href="{{ route('admin.settings') }}">{{ trans('global.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
