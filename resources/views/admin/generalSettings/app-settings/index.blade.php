@extends('layouts.admin')

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-3 settings_bar_gap">
                <div class="box box-info box_info settings-sidebar-card">
                    <h4 class="all_settings f-18 mt-1">{{ trans('global.app_settings') }}</h4>
                    @include('admin.generalSettings.general-setting-links.links')
                </div>
            </div>

            <div class="col-md-9">
                <div class="settings-page-header">
                    <div>
                        <span class="settings-page-header__eyebrow">Mobile app</span>
                        <h1 class="settings-page-header__title">{{ trans('global.general_app_settings') }}</h1>
                        <p class="settings-page-header__subtitle">Tune driver polling, location refresh, and Google-powered distance behavior used by the rider and driver apps.</p>
                    </div>
                </div>

                <form id="app_settings_form" method="POST" action="{{ route('admin.settings.app.update') }}"
                    class="settings-card settings-form-card" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="settings-card__header">
                        <div>
                            <h3>Operational intervals</h3>
                            <p>These values affect mobile polling behavior and location accuracy expectations.</p>
                        </div>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label for="firebase_update_interval">Minimum Firebase Location Update Time (seconds) <span class="text-danger">*</span></label>
                            <input class="form-control" type="number" min="1" name="firebase_update_interval"
                                id="firebase_update_interval" value="{{ $firebase_update_interval ?? '' }}" required>
                        </div>

                        <div class="settings-field">
                            <label for="location_accuracy_threshold">Location Accuracy Threshold (km)</label>
                            <input class="form-control" type="number" step="0.1" name="location_accuracy_threshold"
                                id="location_accuracy_threshold" value="{{ $location_accuracy_threshold ?? '' }}">
                        </div>

                        <div class="settings-field">
                            <label for="background_location_interval">Background Location Update Interval (seconds)</label>
                            <input class="form-control" type="number" min="1" name="background_location_interval"
                                id="background_location_interval" value="{{ $background_location_interval ?? '' }}">
                        </div>

                        <div class="settings-field">
                            <label for="driver_search_interval">Nearby Driver Search Interval (seconds)</label>
                            <input class="form-control" type="number" min="1" name="driver_search_interval"
                                id="driver_search_interval" value="{{ $driver_search_interval ?? '' }}">
                        </div>

                        <div class="settings-field">
                            <label for="minimum_hits_time">Minimum Hits Time After Pickup (seconds)</label>
                            <input class="form-control" type="number" min="1" name="minimum_hits_time"
                                id="minimum_hits_time" value="{{ $minimum_hits_time ?? '' }}">
                        </div>
                    </div>

                    <div class="settings-card__header">
                        <div>
                            <h3>Google routing behavior</h3>
                            <p>Control where app UIs should rely on Google Maps for distance and ETA display.</p>
                        </div>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label for="use_google_after_pickup">Use Google after pickup?</label>
                            <select class="form-control" name="use_google_after_pickup" id="use_google_after_pickup">
                                <option value="1" {{ (isset($use_google_after_pickup) && $use_google_after_pickup == 1) ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ (isset($use_google_after_pickup) && $use_google_after_pickup == 0) ? 'selected' : '' }}>No</option>
                            </select>
                        </div>

                        <div class="settings-field">
                            <label for="use_google_before_pickup">Use Google before pickup?</label>
                            <select class="form-control" name="use_google_before_pickup" id="use_google_before_pickup">
                                <option value="1" {{ (isset($use_google_before_pickup) && $use_google_before_pickup == 1) ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ (isset($use_google_before_pickup) && $use_google_before_pickup == 0) ? 'selected' : '' }}>No</option>
                            </select>
                        </div>

                        <div class="settings-field settings-field--full">
                            <label for="use_google_source_destination">Use Google from source to destination?</label>
                            <select class="form-control" name="use_google_source_destination" id="use_google_source_destination">
                                <option value="1" {{ (isset($use_google_source_destination) && $use_google_source_destination == 1) ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ (isset($use_google_source_destination) && $use_google_source_destination == 0) ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>

                    <div class="settings-card__footer">
                        <button type="submit" class="btn btn-primary btn-space">{{ trans('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#app_settings_form').on('submit', function(event) {
                event.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        toastr.success(response.success, 'Success', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    error: function(response) {
                        toastr.error(response.responseJSON?.message || 'Form submission failed.', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });
        });
    </script>
@endsection
