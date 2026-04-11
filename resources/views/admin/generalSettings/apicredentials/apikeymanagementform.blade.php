@extends('layouts.admin')
@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-3 settings_bar_gap">
                <div class="box box-info box_info">
                    <div class="">
                        <h4 class="all_settings f-18 mt-1" style="margin-left:15px;">{{ trans('global.manage_settings') }}
                        </h4>
                        @include('admin.generalSettings.general-setting-links.links')
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="box box-info">

                    <div class="box-header with-border">
                        <h3 class="box-title">{{ trans('global.apiCredentials_title_singular') }}</h3><span
                            class="email_status" style="display: none;">(<span class="text-green"><i class="fa fa-check"
                                    aria-hidden="true"></i>Verified</span>)</span>
                    </div>

	                    <form id="api_credentials" method="post" action="{{ route('admin.apiauthenticationadd') }}"
	                        class="form-horizontal" enctype="multipart/form-data" novalidate="novalidate">
	                        {{ csrf_field() }}
                            @php
                                $settingValue = function ($setting) {
                                    return is_object($setting) ? ($setting->meta_value ?? '') : ($setting ?? '');
                                };
                            @endphp

                        <div class="form-group google_client_secret">

                        </div>
                        <div class="form-group google_map_key">
                            <label for="inputEmail3" class="col-sm-3 control-label">{{ trans('global.google_mao_browser') }}
                                <span class="text-danger">*</span></label>
                            <div class="col-sm-6">
	                                <input type="password" name="api_google_map_key" class="form-control" id="google_map_key"
	                                    value="{{ $settingValue($api_google_map_key ?? '') }}"
	                                    placeholder="Google Map Browser Key">
                                <span class="text-danger"></span>
                            </div>
                            <div class="col-sm-3">
                                <small></small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="general_captcha" class="col-sm-3 control-label">{{ trans('global.captcha') }} <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-6">
	                                <select name="general_captcha" id="general_captcha" class="form-control">
	                                    <option value="yes"
	                                        {{ $settingValue($general_captcha ?? '') == 'yes' ? 'selected' : '' }}>
	                                        Yes</option>
	                                    <option value="no"
	                                        {{ $settingValue($general_captcha ?? '') == 'no' ? 'selected' : '' }}>No
	                                    </option>
	                                </select>
                                <span class="text-danger"></span>
                            </div>
                            <div class="col-sm-3">
                                <small></small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="site_key" class="col-sm-3 control-label">{{ trans('global.site_key') }} <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-6">
	                                <input type="password" name="site_key" id="site_key" class="form-control"
	                                    value="{{ $settingValue($site_key ?? '') }}" required>
                                <span class="text-danger"></span>
                            </div>
                            <div class="col-sm-3">
                                <small></small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="private_key" class="col-sm-3 control-label">{{ trans('global.private_key') }} <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-6">
	                                <input type="password" name="private_key" id="private_key" class="form-control"
	                                    value="{{ $settingValue($private_key ?? '') }}" required>
	                                <span class="text-danger"></span>
	                            </div>
	                            <div class="col-sm-3">
	                                <small></small>
	                            </div>
	                        </div>

                            <hr>
                            <h4 class="text-center" style="margin-bottom:20px;">Exotel (Call Masking)</h4>

                            <div class="form-group">
                                <label for="exotel_sid" class="col-sm-3 control-label">Exotel SID</label>
                                <div class="col-sm-6">
                                    <input type="text" name="exotel_sid" id="exotel_sid" class="form-control"
                                        value="{{ $settingValue($exotel_sid ?? '') }}" placeholder="your-exotel-sid">
                                    <span class="text-danger"></span>
                                </div>
                                <div class="col-sm-3">
                                    <small></small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="exotel_token" class="col-sm-3 control-label">Exotel Token</label>
                                <div class="col-sm-6">
                                    <input type="password" name="exotel_token" id="exotel_token" class="form-control"
                                        value="{{ $settingValue($exotel_token ?? '') }}" placeholder="exotel-token">
                                    <span class="text-danger"></span>
                                </div>
                                <div class="col-sm-3">
                                    <small></small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="exotel_virtual_number" class="col-sm-3 control-label">Virtual Number</label>
                                <div class="col-sm-6">
                                    <input type="text" name="exotel_virtual_number" id="exotel_virtual_number"
                                        class="form-control" value="{{ $settingValue($exotel_virtual_number ?? '') }}"
                                        placeholder="Exophone / caller id">
                                    <span class="text-danger"></span>
                                </div>
                                <div class="col-sm-3">
                                    <small></small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="exotel_base_url" class="col-sm-3 control-label">Exotel Base URL</label>
                                <div class="col-sm-6">
                                    <input type="text" name="exotel_base_url" id="exotel_base_url" class="form-control"
                                        value="{{ $settingValue($exotel_base_url ?? '') }}"
                                        placeholder="https://api.exotel.com/v1/Accounts">
                                    <span class="text-danger"></span>
                                </div>
                                <div class="col-sm-3">
                                    <small></small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="exotel_callback_token" class="col-sm-3 control-label">Callback Token</label>
                                <div class="col-sm-6">
                                    <input type="password" name="exotel_callback_token" id="exotel_callback_token"
                                        class="form-control" value="{{ $settingValue($exotel_callback_token ?? '') }}"
                                        placeholder="Long random secret">
                                    <span class="text-danger"></span>
                                </div>
                                <div class="col-sm-3">
                                    <small></small>
                                </div>
                            </div>
                        <div class="text-center" id="error-message"></div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-info btn-space">{{ trans('global.save') }}</button>
                            <a class="btn btn-danger"
                                href="{{ route('admin.settings') }}">{{ trans('global.cancel') }}</a>
                        </div>

						<div class="form-group">
    <div class="col-sm-3 control-label"></div>
    <div class="col-sm-6">
        <p class="help-block" style="margin-top:10px; color:#777;">
            To create your Google reCAPTCHA v2 keys, visit:
            <a href="https://www.google.com/recaptcha/admin/create" target="_blank">
                https://www.google.com/recaptcha/admin/create
            </a>
            <br>
            After creating the reCAPTCHA, copy the Site Key and Secret Key and paste them in the fields above.
        </p>
    </div>
</div>

                </div>

                </form>
            </div>
        </div>

    </section>
@endsection
