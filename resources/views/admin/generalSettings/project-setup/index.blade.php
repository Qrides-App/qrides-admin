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
                        <span class="settings-page-header__eyebrow">Maintenance</span>
                        <h1 class="settings-page-header__title">{{ trans('global.project_setup') }}</h1>
                        <p class="settings-page-header__subtitle">Refresh caches, repair storage linking, and keep scheduled jobs visible to the team.</p>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card__header">
                        <div>
                            <h3>System refresh</h3>
                            <p>Use this when uploads, cached settings, or stale routes start behaving unexpectedly.</p>
                        </div>
                    </div>

                    <div class="maintenance-grid">
                        <div class="maintenance-action maintenance-action--primary">
                            <div class="maintenance-action__icon"><i class="fa fa-refresh"></i></div>
                            <div class="maintenance-action__content">
                                <h4>{{ trans('global.project_setup') }}</h4>
                                <p>Clears cached views, routes, and config, then repairs the public storage link only when needed.</p>
                            </div>
                            <button id="setupButton" class="btn btn-primary">{{ trans('global.project_setup') }}</button>
                        </div>

                        <div class="maintenance-action maintenance-action--danger">
                            <div class="maintenance-action__icon"><i class="fa fa-trash"></i></div>
                            <div class="maintenance-action__content">
                                <h4>{{ trans('global.project_cleanup') }}</h4>
                                <p>Danger zone. This removes operational data for resets and should only be used intentionally.</p>
                            </div>
                            <button id="cleanupButton" class="btn btn-danger">{{ trans('global.project_cleanup') }}</button>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card__header">
                        <div>
                            <h3>{{ trans('global.cron_job_settings') }}</h3>
                            <p>Schedule these commands on the server so queues, reminders, and background tasks keep running.</p>
                        </div>
                    </div>

                    <div class="settings-callout">
                        <i class="fa fa-info-circle"></i>
                        <span>Both jobs should be installed on production. The queue worker command is long-running and should be supervised.</span>
                    </div>

                    <div class="command-stack">
                        <div class="command-box">
                            <button class="btn btn-default btn-xs copy-btn" onclick="copyToClipboard(this)">
                                <i class="fa fa-copy"></i> {{ trans('global.copy') }}
                            </button>
                            <span class="copy-success"><i class="fa fa-check"></i> Copied</span>
                            <code>* * * * * php -q {{ base_path('artisan') }} schedule:run</code>
                        </div>

                        <div class="command-box">
                            <button class="btn btn-default btn-xs copy-btn" onclick="copyToClipboard(this)">
                                <i class="fa fa-copy"></i> {{ trans('global.copy') }}
                            </button>
                            <span class="copy-success"><i class="fa fa-check"></i> Copied</span>
                            <code>* * * * * php -q {{ base_path('artisan') }} queue:work --tries=3 --timeout=90 --sleep=3</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        function copyToClipboard(button) {
            const commandBox = button.closest('.command-box');
            const codeElement = commandBox.querySelector('code');
            const successMessage = commandBox.querySelector('.copy-success');
            const textarea = document.createElement('textarea');

            textarea.value = codeElement.innerText;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            successMessage.style.display = 'inline-flex';
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 1500);
        }

        $(document).ready(function() {
            $('#setupButton').on('click', function(event) {
                event.preventDefault();
                const $button = $(this);
                $button.attr('disabled', true).text('Processing...');

                $.ajax({
                    url: "{{ route('admin.project_setup_update') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $button.attr('disabled', false).text("{{ trans('global.project_setup') }}");
                        toastr.success(response.message, 'Success', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    error: function(xhr) {
                        $button.attr('disabled', false).text("{{ trans('global.project_setup') }}");
                        toastr.error(xhr.responseJSON?.message || 'An error occurred. Please try again.', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });

            $('#cleanupButton').on('click', function(event) {
                event.preventDefault();

                Swal.fire({
                    title: '{{ trans('global.are_you_sure') }}',
                    text: 'This will clear core operational data. Continue only if you intend to reset the project.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0f766e',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, continue'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    const $button = $(this);
                    $button.attr('disabled', true).text('Processing...');

                    $.ajax({
                        url: "{{ route('admin.project_cleanup_update') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        complete: function() {
                            $button.attr('disabled', false).text("{{ trans('global.project_cleanup') }}");
                        },
                        success: function(response) {
                            toastr.success(response.message || 'Cleanup completed.', 'Success', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'Cleanup request failed.', 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                });
            });
        });
    </script>
@endsection
