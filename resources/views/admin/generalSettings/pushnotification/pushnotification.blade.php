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
                        <span class="settings-page-header__eyebrow">Notification center</span>
                        <h1 class="settings-page-header__title">Push Notification Settings</h1>
                        <p class="settings-page-header__subtitle">Configure provider credentials and broadcast alerts to riders and drivers from one screen.</p>
                    </div>
                    <div class="settings-page-header__actions">
                        <span class="settings-status-pill {{ $pushnotification_status === 'firebase' ? 'is-muted' : 'is-live' }}">
                            {{ $pushnotification_status === 'firebase' ? 'Firebase active' : 'OneSignal active' }}
                        </span>
                    </div>
                </div>

                <div class="settings-card settings-card--hero">
                    <div class="settings-card__header">
                        <div>
                            <h3>Delivery channel</h3>
                            <p>Choose the provider that will handle app notifications.</p>
                        </div>
                    </div>
                    <div class="notification-provider-grid">
                        <label class="notification-provider-card">
                            <input type="checkbox" class="provider-toggle" id="onesignal_status"
                                {{ $pushnotification_status !== 'firebase' ? 'checked' : '' }}>
                            <span class="notification-provider-card__body">
                                <span class="notification-provider-card__title">OneSignal</span>
                                <span class="notification-provider-card__copy">Recommended for the current admin workflow. Credentials below are saved in database settings.</span>
                            </span>
                        </label>

                        <label class="notification-provider-card notification-provider-card--muted">
                            <input type="checkbox" class="provider-toggle" id="firebase_status"
                                {{ $pushnotification_status === 'firebase' ? 'checked' : '' }}>
                            <span class="notification-provider-card__body">
                                <span class="notification-provider-card__title">Firebase</span>
                                <span class="notification-provider-card__copy">Supported by the app layer. Server messaging currently uses your environment and storage credentials.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <form id="general_form" method="POST" action="{{ route('admin.pushnotificationupdate') }}"
                    class="settings-card settings-form-card" enctype="multipart/form-data" novalidate="novalidate">
                    {{ csrf_field() }}

                    <div class="push-provider-pane {{ $pushnotification_status === 'firebase' ? '' : 'is-active' }}" data-provider-pane="onesignal">
                        <div class="settings-card__header">
                            <div>
                                <h3>OneSignal credentials</h3>
                                <p>Keep separate credentials for the rider app and the driver app.</p>
                            </div>
                        </div>

                        <div class="settings-form-grid">
                            <div class="settings-field">
                                <label for="onesignal_app_id">App ID <span class="text-danger">*</span></label>
                                <input class="form-control" type="password" name="onesignal_app_id" id="onesignal_app_id"
                                    placeholder="Rider OneSignal app ID" value="{{ $onesignal_app_id ?? '' }}">
                            </div>

                            <div class="settings-field">
                                <label for="onesignal_rest_api_key">REST API Key <span class="text-danger">*</span></label>
                                <input class="form-control" type="password" name="onesignal_rest_api_key"
                                    id="onesignal_rest_api_key" placeholder="Rider REST API key"
                                    value="{{ $onesignal_rest_api_key ?? '' }}">
                            </div>

                            <div class="settings-field">
                                <label for="onesignal_app_id_driver">Driver App ID <span class="text-danger">*</span></label>
                                <input class="form-control" type="password" name="onesignal_app_id_driver"
                                    id="onesignal_app_id_driver" placeholder="Driver OneSignal app ID"
                                    value="{{ $onesignal_app_id_driver ?? '' }}">
                            </div>

                            <div class="settings-field">
                                <label for="onesignal_rest_api_key_driver">Driver REST API Key <span class="text-danger">*</span></label>
                                <input class="form-control" type="password" name="onesignal_rest_api_key_driver"
                                    id="onesignal_rest_api_key_driver" placeholder="Driver REST API key"
                                    value="{{ $onesignal_rest_api_key_driver ?? '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="push-provider-pane {{ $pushnotification_status === 'firebase' ? 'is-active' : '' }}" data-provider-pane="firebase">
                        <div class="settings-card__header">
                            <div>
                                <h3>Firebase configuration</h3>
                                <p>Use a service account file for HTTP v1, or save a legacy server key as a fallback.</p>
                            </div>
                        </div>

                        <div class="settings-form-grid">
                            <div class="settings-field settings-field--full">
                                <div class="settings-callout settings-callout--soft push-provider-callout">
                                    <i class="fa fa-info-circle"></i>
                                    <span>Preferred setup: upload your Firebase service account file on the server at <strong>{{ $firebaseCredentialsPath }}</strong>. If you do not have that yet, you can still use the legacy server key below.</span>
                                </div>
                            </div>

                            <div class="settings-field">
                                <label>Credentials file</label>
                                <div class="settings-status-pill {{ $firebaseCredentialsExists ? 'is-live' : 'is-muted' }}">
                                    {{ $firebaseCredentialsExists ? 'Detected on server' : 'Missing on server' }}
                                </div>
                            </div>

                            <div class="settings-field">
                                <label>Firebase project</label>
                                <div class="settings-status-pill {{ $firebaseProjectId ? 'is-live' : 'is-muted' }}">
                                    {{ $firebaseProjectId ?: 'Project ID not detected' }}
                                </div>
                            </div>

                            <div class="settings-field settings-field--full">
                                <label for="firebase_server_key">Legacy Firebase server key</label>
                                <input class="form-control" type="password" name="firebase_server_key" id="firebase_server_key"
                                    placeholder="Optional if HTTP v1 credentials are configured"
                                    value="{{ $firebase_server_key ?? '' }}">
                                <p class="settings-help-copy">Leave this empty if HTTP v1 credentials are already available on the server.</p>
                            </div>

                            <div class="settings-field settings-field--full">
                                <div class="settings-status-pill {{ $firebaseReady ? 'is-live' : 'is-muted' }}">
                                    {{ $firebaseReady ? 'Firebase is ready to send notifications' : 'Firebase is not ready yet' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card__footer">
                        <button type="submit" class="btn btn-primary btn-space">{{ trans('global.save') }}</button>
                    </div>
                </form>

                <div class="settings-card settings-form-card">
                    <div class="settings-card__header">
                        <div>
                            <h3>Broadcast to riders</h3>
                            <p>Send a one-time alert to all riders or a selected rider account.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.sendusermessage') }}" class="user_message_form settings-inline-form"
                        enctype="multipart/form-data" novalidate="novalidate">
                        {{ csrf_field() }}
                        <input type="hidden" name="user_type" value="user" />

                        <div class="settings-form-grid settings-form-grid--message">
                            <div class="settings-field">
                                <label for="userid_id">{{ trans('global.user') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="userid_id" id="userid_id" required>
                                    <option value="All">All riders</option>
                                    @foreach ($userids as $id => $namePhoneEmail)
                                        @if ($id !== '')
                                            <option value="{{ $id }}" {{ old('userid_id') == $id ? 'selected' : '' }}>
                                                {{ $namePhoneEmail }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="settings-field">
                                <label for="rider_subject">{{ trans('global.subject') }} <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="subject" id="rider_subject"
                                    placeholder="Ride updates, promo, outage notice..." value="{{ old('subject') }}">
                            </div>

                            <div class="settings-field settings-field--full">
                                <label for="rider_message">{{ trans('global.message') }} <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="message" id="rider_message"
                                    placeholder="Write a concise notification message." rows="5">{{ old('message') }}</textarea>
                            </div>
                        </div>

                        <div class="settings-card__footer">
                            <button type="submit" class="btn btn-primary btn-space">{{ trans('global.send_message') }}</button>
                        </div>
                    </form>
                </div>

                <div class="settings-card settings-form-card">
                    <div class="settings-card__header">
                        <div>
                            <h3>Broadcast to drivers</h3>
                            <p>Notify active drivers about dispatch, operations, or policy changes.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.sendusermessage') }}" class="user_message_form settings-inline-form"
                        enctype="multipart/form-data" novalidate="novalidate">
                        {{ csrf_field() }}
                        <input type="hidden" name="user_type" value="driver" />

                        <div class="settings-form-grid settings-form-grid--message">
                            <div class="settings-field">
                                <label for="drivers">{{ trans('global.user') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="userid_id" id="drivers" required>
                                    <option value="All">All drivers</option>
                                    @foreach ($drivers as $id => $namePhoneEmail)
                                        @if ($id !== '')
                                            <option value="{{ $id }}" {{ old('drivers') == $id ? 'selected' : '' }}>
                                                {{ $namePhoneEmail }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="settings-field">
                                <label for="driver_subject">{{ trans('global.subject') }} <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="subject" id="driver_subject"
                                    placeholder="Shift reminder, dispatch update..." value="{{ old('subject') }}">
                            </div>

                            <div class="settings-field settings-field--full">
                                <label for="driver_message">{{ trans('global.message') }} <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="message" id="driver_message"
                                    placeholder="Write a concise notification message." rows="5">{{ old('message') }}</textarea>
                            </div>
                        </div>

                        <div class="settings-card__footer">
                            <button type="submit" class="btn btn-primary btn-space">{{ trans('global.send_message') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@include('admin.generalSettings.toastermsgDemo')

@section('scripts')
    <script>
        $(document).ready(function() {
            function notifySuccess(message) {
                toastr.success(message, 'Success', {
                    closeButton: true,
                    progressBar: true,
                    positionClass: 'toast-bottom-right'
                });
            }

            function notifyError(message) {
                toastr.error(message || 'Something went wrong.', 'Error', {
                    closeButton: true,
                    progressBar: true,
                    positionClass: 'toast-bottom-right'
                });
            }

            $('#general_form').on('submit', function(event) {
                event.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        notifySuccess(response.success || 'Push notification credentials updated successfully.');
                    },
                    error: function(xhr) {
                        notifyError(xhr.responseJSON?.message || xhr.responseJSON?.error);
                    }
                });
            });

            $('.user_message_form').on('submit', function(event) {
                event.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        notifySuccess(response.success || 'Notification sent successfully.');
                    },
                    error: function(xhr) {
                        notifyError(xhr.responseJSON?.message || xhr.responseJSON?.error);
                    }
                });
            });

            function updateCheckboxStatus(type) {
                $.ajax({
                    url: "{{ route('admin.updatePushNotificationStatus') }}",
                    type: 'POST',
                    data: { type: type },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        notifySuccess('Notification provider updated successfully.');
                    },
                    error: function(xhr) {
                        notifyError(xhr.responseJSON?.message || xhr.responseJSON?.error);
                    }
                });
            }

            function toggleProviderPane(provider) {
                $('[data-provider-pane]').removeClass('is-active');
                $('[data-provider-pane="' + provider + '"]').addClass('is-active');
            }

            $('#firebase_status').on('change', function() {
                if ($(this).prop('checked')) {
                    $('#onesignal_status').prop('checked', false);
                    updateCheckboxStatus('firebase');
                    $('.settings-page-header__actions .settings-status-pill').removeClass('is-live').addClass('is-muted').text('Firebase active');
                    toggleProviderPane('firebase');
                    return;
                }

                $('#onesignal_status').prop('checked', true);
            });

            $('#onesignal_status').on('change', function() {
                if ($(this).prop('checked')) {
                    $('#firebase_status').prop('checked', false);
                    updateCheckboxStatus('onesignal');
                    $('.settings-page-header__actions .settings-status-pill').removeClass('is-muted').addClass('is-live').text('OneSignal active');
                    toggleProviderPane('onesignal');
                    return;
                }

                $('#firebase_status').prop('checked', true);
            });

            toggleProviderPane($('#firebase_status').prop('checked') ? 'firebase' : 'onesignal');
        });
    </script>
@endsection
