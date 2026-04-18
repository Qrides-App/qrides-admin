@extends('layouts.admin')

@section('content')
    @php
        $logoPath = !empty($branding['general_logo']) ? asset('storage/' . $branding['general_logo']) : null;
        $appName = $branding['general_name'] ?? config('app.name');
        $routeMap = [
            'user' => 'user.email-templates',
            'vendor' => 'vendor.email-templates',
            'admin' => 'admin.email-templates',
        ];
        $saveRouteMap = [
            'user' => 'user.email-template.create',
            'vendor' => 'vendor.email-template.create',
            'admin' => 'admin.email-template.create',
        ];
        $scopeLabel = ucfirst($scope);
        $activeRoute = $routeMap[$scope] ?? 'user.email-templates';
        $saveRoute = $saveRouteMap[$scope] ?? 'user.email-template.create';
        $availableRoles = array_filter(explode('#', (string) $emaildata->role));
    @endphp

    <section class="content">
        <div class="settings-page-header">
            <div>
                <span class="settings-page-header__eyebrow">Email templates</span>
                <h1 class="settings-page-header__title">{{ $emaildata->temp_name }}</h1>
                <p class="settings-page-header__subtitle">Preview how the message renders, update the live template body, and send a safe test email before using it in production.</p>
            </div>
        </div>

        <div class="settings-card mail-template-shell">
            <div class="mail-template-shell__toolbar">
                <div class="mail-template-shell__tabs">
                    @foreach ($AllEmailRecord as $data)
                        <a href="{{ route($activeRoute, ['id' => $data->id]) }}" class="mail-template-tab {{ (int) $emaildata->id === (int) $data->id ? 'is-active' : '' }}">
                            {{ $data->temp_name }}
                        </a>
                    @endforeach
                </div>

                <div class="mail-scope-switcher">
                    @foreach ($availableRoles as $role)
                        @php
                            $roleKey = trim($role);
                        @endphp
                        @if (isset($routeMap[$roleKey]))
                            <a href="{{ route($routeMap[$roleKey], ['id' => $emaildata->id]) }}" class="mail-scope-pill {{ $scope === $roleKey ? 'is-active' : '' }}">
                                {{ ucfirst($roleKey) }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="settings-card">
                    <div class="settings-card__header">
                        <div>
                            <h3>Mail Preview</h3>
                            <p>Live preview with sample placeholder values applied for {{ strtolower($scopeLabel) }} messages.</p>
                        </div>
                    </div>

                    <div class="mail-preview-shell mail-preview-shell--template">
                        <div class="mail-preview-shell__header">
                            @if ($logoPath)
                                <img src="{{ $logoPath }}" alt="{{ $appName }}">
                            @endif
                            <div>
                                <h4>{{ $appName }}</h4>
                                <p>{{ $branding['general_email'] ?? 'support@example.com' }}</p>
                            </div>
                        </div>

                        <div class="mail-preview-shell__subject" id="mail-preview-subject">{{ $preview['subject'] ?: 'Your email subject will appear here.' }}</div>

                        <div class="mail-preview-shell__body" id="mail-preview-body">
                            {!! $preview['body'] ?: '<p>Your template preview will appear here.</p>' !!}
                        </div>

                        <div class="mail-preview-shell__footer">
                            <span>{{ $branding['general_email'] ?? 'support@example.com' }}</span>
                            <span>{{ ($branding['general_default_phone_country'] ?? '+91') . ' ' . ($branding['general_phone'] ?? '9876543210') }}</span>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card__header">
                        <div>
                            <h3>Send Test Mail</h3>
                            <p>The current subject and editor content will be sent to the email below using sample values.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.email-template.test', ['id' => $emaildata->id]) }}" id="template_test_form">
                        @csrf
                        <input type="hidden" name="subject" id="test_mail_subject">
                        <input type="hidden" name="body" id="test_mail_body">

                        <div class="form-group">
                            <label>Recipient Email</label>
                            <input type="email" name="recipient_email" class="form-control" value="{{ old('recipient_email') }}" placeholder="you@example.com">
                        </div>

                        <div class="settings-actions settings-actions--compact">
                            <button type="submit" class="btn btn-primary settings-btn-primary">Send Test Mail</button>
                        </div>
                    </form>
                </div>

                <div class="settings-card">
                    <div class="settings-card__header">
                        <div>
                            <h3>Sample Variables</h3>
                            <p>These placeholders are resolved in preview and test sends.</p>
                        </div>
                    </div>

                    <div class="mail-variable-cloud">
                        @foreach ($sampleData as $key => $value)
                            <span class="mail-variable-chip">{{ '{{' . $key . '}}' }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <form action="{{ route($saveRoute, ['id' => $emaildata->id]) }}" method="POST" id="template_editor_form">
                    @csrf
                    @if ($scope === 'vendor')
                        <input type="hidden" name="type" value="vendor">
                    @elseif ($scope === 'admin')
                        <input type="hidden" name="type" value="admin">
                    @endif

                    <div class="settings-card">
                        <div class="settings-card__header">
                            <div>
                                <h3>{{ $scopeLabel }} Template Setup</h3>
                                <p>Update the subject, email body, and delivery channels for this notification.</p>
                            </div>
                        </div>

                        <div class="mail-settings-grid">
                            <div class="form-group mail-settings-grid__full">
                                <label>Subject</label>
                                @if ($scope === 'vendor')
                                    <input type="text" name="vendorsubject" id="template_subject_input" class="form-control" value="{{ old('vendorsubject', $templateConfig['subject']) }}" placeholder="Template subject">
                                @elseif ($scope === 'admin')
                                    <input type="text" name="adminsubject" id="template_subject_input" class="form-control" value="{{ old('adminsubject', $templateConfig['subject']) }}" placeholder="Template subject">
                                @else
                                    <input type="text" name="subject" id="template_subject_input" class="form-control" value="{{ old('subject', $templateConfig['subject']) }}" placeholder="Template subject">
                                @endif
                            </div>
                        </div>

                        <div class="mail-channel-switches">
                            @if ($scope === 'vendor')
                                <label class="settings-switch">
                                    <input type="checkbox" name="vendoremailsent" value="1" {{ old('vendoremailsent', $templateConfig['email_enabled']) ? 'checked' : '' }}>
                                    <span class="settings-switch__slider"></span>
                                    <span class="settings-switch__label">Email</span>
                                </label>
                                <label class="settings-switch">
                                    <input type="checkbox" name="vendorsmssent" value="1" {{ old('vendorsmssent', $templateConfig['sms_enabled']) ? 'checked' : '' }}>
                                    <span class="settings-switch__slider"></span>
                                    <span class="settings-switch__label">SMS</span>
                                </label>
                                <label class="settings-switch">
                                    <input type="checkbox" name="vendorpushsent" value="1" {{ old('vendorpushsent', $templateConfig['push_enabled']) ? 'checked' : '' }}>
                                    <span class="settings-switch__slider"></span>
                                    <span class="settings-switch__label">Push</span>
                                </label>
                            @elseif ($scope === 'admin')
                                <label class="settings-switch">
                                    <input type="checkbox" name="adminemailsent" value="1" {{ old('adminemailsent', $templateConfig['email_enabled']) ? 'checked' : '' }}>
                                    <span class="settings-switch__slider"></span>
                                    <span class="settings-switch__label">Email</span>
                                </label>
                            @else
                                <label class="settings-switch">
                                    <input type="checkbox" name="emailsent" value="1" {{ old('emailsent', $templateConfig['email_enabled']) ? 'checked' : '' }}>
                                    <span class="settings-switch__slider"></span>
                                    <span class="settings-switch__label">Email</span>
                                </label>
                                <label class="settings-switch">
                                    <input type="checkbox" name="smssent" value="1" {{ old('smssent', $templateConfig['sms_enabled']) ? 'checked' : '' }}>
                                    <span class="settings-switch__slider"></span>
                                    <span class="settings-switch__label">SMS</span>
                                </label>
                                <label class="settings-switch">
                                    <input type="checkbox" name="pushsent" value="1" {{ old('pushsent', $templateConfig['push_enabled']) ? 'checked' : '' }}>
                                    <span class="settings-switch__slider"></span>
                                    <span class="settings-switch__label">Push</span>
                                </label>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>Email Body</label>
                            @if ($scope === 'vendor')
                                <textarea name="vendorbody" id="template_body_editor" class="form-control mail-template-editor">{{ old('vendorbody', $templateConfig['body']) }}</textarea>
                            @elseif ($scope === 'admin')
                                <textarea name="adminbody" id="template_body_editor" class="form-control mail-template-editor">{{ old('adminbody', $templateConfig['body']) }}</textarea>
                            @else
                                <textarea name="body" id="template_body_editor" class="form-control mail-template-editor">{{ old('body', $templateConfig['body']) }}</textarea>
                            @endif
                        </div>

                        @if ($scope !== 'admin')
                            <div class="mail-settings-grid">
                                <div class="form-group">
                                    <label>SMS Copy</label>
                                    @if ($scope === 'vendor')
                                        <textarea class="form-control" name="vendorsms" rows="5" placeholder="Vendor SMS content">{{ old('vendorsms', $templateConfig['sms']) }}</textarea>
                                    @else
                                        <textarea class="form-control" name="sms" rows="5" placeholder="SMS content">{{ old('sms', $templateConfig['sms']) }}</textarea>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>Push Notification Copy</label>
                                    @if ($scope === 'vendor')
                                        <textarea class="form-control" name="vendorpush_notification" rows="5" placeholder="Vendor push notification copy">{{ old('vendorpush_notification', $templateConfig['push']) }}</textarea>
                                    @else
                                        <textarea class="form-control" name="push_notification" rows="5" placeholder="Push notification copy">{{ old('push_notification', $templateConfig['push']) }}</textarea>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="settings-actions">
                            <button type="reset" class="btn btn-default settings-btn-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary settings-btn-primary">Save Template</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sampleData = @json($sampleData);
            const subjectInput = document.getElementById('template_subject_input');
            const bodyField = document.getElementById('template_body_editor');
            const previewSubject = document.getElementById('mail-preview-subject');
            const previewBody = document.getElementById('mail-preview-body');
            const testSubject = document.getElementById('test_mail_subject');
            const testBody = document.getElementById('test_mail_body');
            let editorInstance = null;

            const applyPreviewTokens = (content) => {
                let output = content || '';
                Object.entries(sampleData).forEach(([key, value]) => {
                    const moustache = new RegExp('@?{{\\s*' + key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\s*}}', 'gi');
                    const braces = new RegExp('{\\s*' + key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\s*}', 'gi');
                    output = output.replace(moustache, value).replace(braces, value);
                });
                return output;
            };

            const syncPreview = () => {
                const subject = applyPreviewTokens(subjectInput?.value || '').trim();
                const body = applyPreviewTokens(editorInstance ? editorInstance.getData() : (bodyField?.value || ''));
                previewSubject.textContent = subject || 'Your email subject will appear here.';
                previewBody.innerHTML = body || '<p>Your template preview will appear here.</p>';
                testSubject.value = subjectInput?.value || '';
                testBody.value = editorInstance ? editorInstance.getData() : (bodyField?.value || '');
            };

            if (window.ClassicEditor && bodyField) {
                window.ClassicEditor.create(bodyField)
                    .then((editor) => {
                        editorInstance = editor;
                        editor.model.document.on('change:data', syncPreview);
                        syncPreview();
                    })
                    .catch(() => {
                        syncPreview();
                    });
            } else {
                syncPreview();
            }

            subjectInput?.addEventListener('input', syncPreview);
            bodyField?.addEventListener('input', syncPreview);
            document.getElementById('template_test_form')?.addEventListener('submit', syncPreview);
        });
    </script>
@endsection
