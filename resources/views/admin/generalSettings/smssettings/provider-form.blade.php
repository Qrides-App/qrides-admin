<section class="content">
    <div class="row">
        <div class="col-md-3 settings_bar_gap">
            <div class="box box-info box_info settings-sidebar-card">
                <h4 class="all_settings f-18 mt-1">Manage Settings</h4>
                @include('admin.generalSettings.general-setting-links.links')
            </div>
        </div>

        <div class="col-md-9">
            @include('admin.generalSettings.smssettings.smsnavicon')

            <form method="POST" action="{{ $formAction }}" class="settings-card settings-form-card smssettingform">
                @csrf

                <div class="settings-card__header">
                    <div>
                        <h3>{{ $cardTitle ?? ($providerLabel . ' credentials') }}</h3>
                        <p>{{ $cardSubtitle ?? 'Save the credentials required to send login OTPs and transactional SMS through this provider.' }}</p>
                    </div>
                    <div class="settings-toggle-wrap">
                        <span class="settings-toggle-wrap__label">Primary provider</span>
                        <label class="settings-switch">
                            <input type="checkbox" class="statusdata"
                                data-url="{{ route('admin.update-sms-provider-name') }}"
                                data-user-value="{{ $providerKey }}"
                                {{ ($sms_provider_name->meta_value ?? null) === $providerKey ? 'checked' : '' }}>
                            <span class="settings-switch__slider"></span>
                        </label>
                    </div>
                </div>

                @if (!empty($callout))
                    <div class="settings-callout settings-callout--soft">
                        <i class="fa {{ $callout['icon'] ?? 'fa-info-circle' }}"></i>
                        <span>{{ $callout['text'] }}</span>
                    </div>
                @endif

                <div class="settings-form-grid">
                    @foreach ($fields as $field)
                        @php
                            $fieldId = $field['id'] ?? $field['name'];
                            $fieldType = $field['type'] ?? 'text';
                            $fieldValue = old($field['name'], $field['value'] ?? '');
                        @endphp
                        <div class="settings-field {{ !empty($field['full']) ? 'settings-field--full' : '' }}">
                            <label for="{{ $fieldId }}">
                                {{ $field['label'] }}
                                @if (($field['required'] ?? true) === true)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>

                            @if (($field['as'] ?? 'input') === 'textarea')
                                <textarea class="form-control" name="{{ $field['name'] }}" id="{{ $fieldId }}"
                                    rows="{{ $field['rows'] ?? 5 }}"
                                    placeholder="{{ $field['placeholder'] ?? $field['label'] }}">{{ $fieldValue }}</textarea>
                            @else
                                <input class="form-control" type="{{ $fieldType }}" name="{{ $field['name'] }}"
                                    id="{{ $fieldId }}"
                                    placeholder="{{ $field['placeholder'] ?? $field['label'] }}"
                                    value="{{ $fieldValue }}">
                            @endif

                            @if (!empty($field['help']))
                                <p class="help-block settings-help-copy">{{ $field['help'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="settings-actions">
                    <a class="btn settings-btn-secondary" href="{{ route('admin.settings') }}">{{ trans('global.cancel') }}</a>
                    <button type="submit" class="btn btn-primary settings-btn-primary">{{ trans('global.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</section>
