@extends('layouts.admin')

@section('content')
    @php
        $i = 0;
        $statusValue = $status->meta_value ?? 'Inactive';
        $checkboxId = 'status_toggle_' . $i++;
        $modes = ['test', 'live'];
        $fields = $fields_per_method ?? [];
    @endphp

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
                        <span class="settings-page-header__eyebrow">Payments</span>
                        <h1 class="settings-page-header__title">{{ $title }} configuration</h1>
                        <p class="settings-page-header__subtitle">Manage status and credentials for each payment gateway from a single modern settings panel.</p>
                    </div>
                    <span class="settings-status-pill {{ $statusValue === 'Active' ? 'is-live' : 'is-muted' }}">
                        {{ $statusValue }}
                    </span>
                </div>

                <div class="settings-card settings-form-card">
                    <div class="settings-card__header">
                        <div>
                            <h3>Gateway details</h3>
                            <p>Keep test and live credentials separate so sandbox changes do not affect production traffic.</p>
                        </div>
                    </div>

                    @include('admin.generalSettings.payment-methods.payment-links')

                    <form method="POST" action="{{ route('admin.payment_methods.update', $method) }}" class="settings-inline-form">
                        @csrf

                        <div class="settings-toolbar">
                            <div class="settings-toggle-wrap">
                                <span class="settings-toggle-wrap__label">Gateway status</span>
                                <label class="settings-switch">
                                    <input class="statusdata" type="checkbox" id="{{ $checkboxId }}"
                                        {{ $statusValue == 'Active' ? 'checked' : '' }}>
                                    <span class="settings-switch__slider"></span>
                                </label>
                            </div>
                        </div>

                        @if ($options_field !== null && count($modes))
                            <div class="gateway-mode-grid">
                                @foreach ($modes as $mode)
                                    <label class="gateway-mode-card">
                                        <input type="radio" name="{{ $options_field }}" value="{{ $mode }}"
                                            id="{{ $method }}_{{ $mode }}"
                                            {{ (isset($$options_field) && $$options_field->meta_value == $mode) || (!isset($$options_field) && $mode == 'test') ? 'checked' : '' }}>
                                        <span>
                                            <strong>{{ ucfirst($mode) }}</strong>
                                            <small>{{ $mode === 'test' ? 'Use for sandbox and QA payments.' : 'Use for real customer transactions.' }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div class="settings-form-grid">
                            @if (count($fields))
                                @foreach ($modes as $mode)
                                    @foreach ($fields as $field)
                                        @php
                                            $key = "{$mode}_{$method}_{$field}";
                                            $label = $field_labels[$mode . '_' . $field] ?? ucfirst(str_replace('_', ' ', $field));
                                        @endphp
                                        <div class="settings-field">
                                            <label for="{{ $key }}">{{ ucfirst($mode) }} {{ $label }} <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="{{ $key }}" name="{{ $key }}"
                                                value="{{ $$key->meta_value ?? '' }}" placeholder="{{ ucfirst($mode) }} {{ $label }}">
                                        </div>
                                    @endforeach
                                @endforeach
                            @else
                                <div class="settings-callout settings-callout--soft">
                                    <i class="fa fa-info-circle"></i>
                                    <span>No additional credentials are required for this payment method. Toggle it on or off as needed.</span>
                                </div>
                            @endif
                        </div>

                        <div class="settings-card__footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> {{ __('global.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    @parent
    <script>
        handleToggleUpdate(
            '.statusdata',
            "{{ url('admin/payment-methods') }}/{{ $method }}/status",
            'status', {
                title: 'Are you sure?',
                text: 'Do you want to update the payment method status?',
                confirmButtonText: 'Yes, update',
                cancelButtonText: 'Cancel'
            }
        );
    </script>
@endsection
