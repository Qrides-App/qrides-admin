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
                        <span class="settings-page-header__eyebrow">Pricing controls</span>
                        <h1 class="settings-page-header__title">{{ trans('global.fees_title_singular') }}</h1>
                        <p class="settings-page-header__subtitle">Tune the tax and platform commission values that influence ride and booking-related financial calculations across the admin platform.</p>
                    </div>
                </div>

                <form id="fees_setting" method="POST" action="{{ route('admin.FeesSetupadd') }}"
                    class="settings-card settings-form-card" novalidate="novalidate">
                    @csrf
                    <input type="hidden" name="feesetup_accomodation_tax_get"
                        value="{{ $feesetup_accomodation_tax_get->meta_value ?? 'admin' }}">

                    <div class="settings-card__header">
                        <div>
                            <h3>Financial rules</h3>
                            <p>Keep these values as percentages. They are used as platform defaults in financial summaries and downstream ride calculations.</p>
                        </div>
                    </div>

                    <div class="settings-callout settings-callout--soft">
                        <i class="fa fa-calculator"></i>
                        <span>Update these values carefully. Changes here affect all future pricing and finance views that rely on global fee defaults.</span>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label for="feesetup_iva_tax">{{ trans('global.iva_tax') }} <span class="text-danger">*</span></label>
                            <input type="text" name="feesetup_iva_tax" class="form-control" id="feesetup_iva_tax"
                                value="{{ old('feesetup_iva_tax', $feesetup_iva_tax->meta_value ?? '') }}"
                                placeholder="e.g. 5 or 18">
                            <p class="help-block settings-help-copy">Enter the platform tax rate as a percentage without the % symbol.</p>
                        </div>

                        <div class="settings-field">
                            <label for="feesetup_admin_commission">{{ trans('global.admin_commission') }} <span class="text-danger">*</span></label>
                            <input type="text" name="feesetup_admin_commission" class="form-control"
                                id="feesetup_admin_commission"
                                value="{{ old('feesetup_admin_commission', $feesetup_admin_commission->meta_value ?? '') }}"
                                placeholder="e.g. 10 or 15">
                            <p class="help-block settings-help-copy">This percentage is retained as the admin commission on eligible bookings and rides.</p>
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
