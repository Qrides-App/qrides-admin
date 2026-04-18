@extends('layouts.admin')

@section('content')
    @php
        $logoPath = !empty($branding['general_logo']) ? asset('storage/' . $branding['general_logo']) : null;
        $appName = $branding['general_name'] ?? config('app.name');
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
                        <span class="settings-page-header__eyebrow">Mail delivery</span>
                        <h1 class="settings-page-header__title">Mail Config</h1>
                        <p class="settings-page-header__subtitle">Configure SMTP, verify delivery with a test message, and keep email flows like OTP and password reset aligned with the new admin UI.</p>
                    </div>
                </div>

                <div class="settings-pill-tabs">
                    <button class="settings-pill-tabs__item is-active" type="button" data-target="mail-config-pane">Mail Config</button>
                    <button class="settings-pill-tabs__item" type="button" data-target="mail-test-pane">Send Test Mail</button>
                </div>

                <div class="mail-config-pane is-active" id="mail-config-pane">
                    <div class="settings-card">
                        <div class="settings-card__header">
                            <div>
                                <h3>SMTP configuration</h3>
                                <p>These values are stored in `general_settings` and used by notification emails, password reset, and template test sends.</p>
                            </div>
                            <label class="settings-switch">
                                <input type="checkbox" form="email_settings_form" name="emailwizard_enabled" value="1" {{ old('emailwizard_enabled', $mailSettings['enabled']) ? 'checked' : '' }}>
                                <span class="settings-switch__slider"></span>
                                <span class="settings-switch__label">Mail {{ old('emailwizard_enabled', $mailSettings['enabled']) ? 'enabled' : 'disabled' }}</span>
                            </label>
                        </div>

                        <div class="settings-callout">
                            <i class="fa fa-info-circle"></i>
                            <span>Leave the password field blank if you want to keep the currently stored SMTP password unchanged.</span>
                        </div>

                        <form id="email_settings_form" method="POST" action="{{ route('admin.email.update') }}">
                            @csrf
                            <div class="mail-settings-grid">
                                <div class="form-group">
                                    <label>Mailer Name</label>
                                    <input type="text" name="mailer_name" class="form-control" value="{{ old('mailer_name', $mailSettings['mailer_name']) }}" placeholder="QRides">
                                </div>
                                <div class="form-group">
                                    <label>Driver</label>
                                    <input type="text" name="driver" class="form-control" value="{{ old('driver', $mailSettings['driver']) }}" placeholder="smtp">
                                </div>
                                <div class="form-group">
                                    <label>Host</label>
                                    <input type="text" name="host" class="form-control" value="{{ old('host', $mailSettings['host']) }}" placeholder="smtp.example.com">
                                </div>
                                <div class="form-group">
                                    <label>Port</label>
                                    <input type="number" name="port" class="form-control" value="{{ old('port', $mailSettings['port']) }}" placeholder="587">
                                </div>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" value="{{ old('username', $mailSettings['username']) }}" placeholder="no-reply@example.com">
                                </div>
                                <div class="form-group">
                                    <label>From Email</label>
                                    <input type="email" name="from_email" class="form-control" value="{{ old('from_email', $mailSettings['from_email']) }}" placeholder="no-reply@example.com">
                                </div>
                                <div class="form-group">
                                    <label>Encryption</label>
                                    <input type="text" name="encryption" class="form-control" value="{{ old('encryption', $mailSettings['encryption']) }}" placeholder="tls">
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" name="password" class="form-control" value="" placeholder="Leave blank to keep current password">
                                </div>
                            </div>

                            <div class="settings-actions">
                                <button type="reset" class="btn btn-default settings-btn-secondary">Reset</button>
                                <button type="submit" class="btn btn-primary settings-btn-primary">Save</button>
                            </div>
                        </form>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card__header">
                            <div>
                                <h3>Preview</h3>
                                <p>This is the shell that template emails will inherit when they are sent through the current mail pipeline.</p>
                            </div>
                        </div>

                        <div class="mail-preview-shell">
                            <div class="mail-preview-shell__header">
                                @if ($logoPath)
                                    <img src="{{ $logoPath }}" alt="{{ $appName }}">
                                @endif
                                <div>
                                    <h4>{{ $mailSettings['mailer_name'] ?: $appName }}</h4>
                                    <p>{{ $mailSettings['from_email'] ?: ($branding['general_email'] ?? 'no-reply@example.com') }}</p>
                                </div>
                            </div>
                            <div class="mail-preview-shell__body">
                                <h3>Sample transactional email</h3>
                                <p>This preview reflects your current mail branding and SMTP sender identity. Template-specific content is edited from the Email Templates section.</p>
                                <p><strong>SMTP host:</strong> {{ $mailSettings['host'] ?: 'Not configured' }}</p>
                                <p><strong>Port:</strong> {{ $mailSettings['port'] ?: 'Not configured' }}</p>
                            </div>
                            <div class="mail-preview-shell__footer">
                                <span>{{ $branding['general_email'] ?? 'support@example.com' }}</span>
                                <span>{{ ($branding['general_default_phone_country'] ?? '+91') . ' ' . ($branding['general_phone'] ?? '9876543210') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mail-config-pane" id="mail-test-pane">
                    <div class="settings-card">
                        <div class="settings-card__header">
                            <div>
                                <h3>Send test mail</h3>
                                <p>Use this after saving SMTP values to confirm delivery, credentials, and sender identity.</p>
                            </div>
                        </div>

                        <div class="settings-callout">
                            <i class="fa fa-paper-plane"></i>
                            <span>The test mail uses the same shell as production notifications and will fail if SMTP is disabled or incomplete.</span>
                        </div>

                        <form method="POST" action="{{ route('admin.email.test') }}" class="mail-test-form">
                            @csrf
                            <div class="mail-settings-grid">
                                <div class="form-group">
                                    <label>Recipient Email</label>
                                    <input type="email" name="recipient_email" class="form-control" value="{{ old('recipient_email') }}" placeholder="you@example.com">
                                </div>
                                <div class="form-group">
                                    <label>Subject</label>
                                    <input type="text" name="subject" class="form-control" value="{{ old('subject', $appName . ' Test Mail') }}" placeholder="QRides Test Mail">
                                </div>
                            </div>

                            <div class="settings-actions">
                                <button type="submit" class="btn btn-primary settings-btn-primary">Send Test Mail</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.settings-pill-tabs__item');
            const panes = document.querySelectorAll('.mail-config-pane');

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    tabs.forEach((item) => item.classList.remove('is-active'));
                    panes.forEach((pane) => pane.classList.remove('is-active'));
                    tab.classList.add('is-active');
                    document.getElementById(tab.dataset.target)?.classList.add('is-active');
                });
            });
        });
    </script>
@endsection
