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
                        <span class="settings-page-header__eyebrow">Finance</span>
                        <h1 class="settings-page-header__title">{{ trans('global.currency_settings_wizard') }}</h1>
                        <p class="settings-page-header__subtitle">Set the default operating currency and keep exchange-rate configuration ready for future multi-currency support.</p>
                    </div>
                </div>

                <form id="fees_setting" method="post" action="{{ route('admin.updateCurrencyAuthKey') }}"
                    class="settings-card settings-form-card" novalidate="novalidate">
                    {{ csrf_field() }}

                    <div class="settings-card__header">
                        <div>
                            <h3>Currency setup</h3>
                            <p>The exchange-rate provider key is optional right now, but the default currency is required for pricing and admin reporting.</p>
                        </div>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field" style="display:none;">
                            <label for="currency_auth_key">Currency Auth Key</label>
                            <input type="password" name="currency_auth_key" class="form-control" id="currency_auth_key"
                                value="{{ $currency_auth_key->meta_value ?? '' }}" placeholder="Exchange rate API key">
                        </div>

                        <div class="settings-field settings-field--full">
                            <label for="default_currency">{{ trans('global.default_currency') }} <span class="text-danger">*</span></label>
                            <select class="form-control validate_field" id="default_currency" name="general_default_currency">
                                @foreach ($allcurrency as $currency)
                                    <option value="{{ $currency->currency_code }}"
                                        @if (($general_default_currency->meta_value ?? null) == $currency->currency_code) selected @endif>
                                        {{ $currency->currency_name }} ({{ $currency->currency_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
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
