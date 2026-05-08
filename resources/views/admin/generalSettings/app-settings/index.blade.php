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
                            <label for="driver_search_medium_supply_threshold">Medium Supply Threshold (drivers)</label>
                            <input class="form-control" type="number" min="1" name="driver_search_medium_supply_threshold"
                                id="driver_search_medium_supply_threshold" value="{{ $driver_search_medium_supply_threshold ?? '' }}">
                        </div>

                        <div class="settings-field">
                            <label for="driver_search_high_supply_threshold">High Supply Threshold (drivers)</label>
                            <input class="form-control" type="number" min="1" name="driver_search_high_supply_threshold"
                                id="driver_search_high_supply_threshold" value="{{ $driver_search_high_supply_threshold ?? '' }}">
                        </div>

                        <div class="settings-field">
                            <label for="driver_search_medium_supply_extra_time">Extra Search Time For Medium Supply (seconds)</label>
                            <input class="form-control" type="number" min="0" name="driver_search_medium_supply_extra_time"
                                id="driver_search_medium_supply_extra_time" value="{{ $driver_search_medium_supply_extra_time ?? '' }}">
                        </div>

                        <div class="settings-field">
                            <label for="driver_search_low_supply_extra_time">Extra Search Time For Low Supply (seconds)</label>
                            <input class="form-control" type="number" min="0" name="driver_search_low_supply_extra_time"
                                id="driver_search_low_supply_extra_time" value="{{ $driver_search_low_supply_extra_time ?? '' }}">
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

                    <div class="settings-card__header">
                        <div>
                            <h3>Rider home banner</h3>
                            <p>Change the rider app promo banner text and colors without publishing a new app build.</p>
                        </div>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label for="rider_home_banner_eyebrow">Banner label</label>
                            <input class="form-control" type="text" name="rider_home_banner_eyebrow"
                                id="rider_home_banner_eyebrow" value="{{ $rider_home_banner_eyebrow ?? 'First ride offer' }}"
                                placeholder="First ride offer">
                        </div>

                        <div class="settings-field">
                            <label for="rider_home_banner_title">Banner headline</label>
                            <input class="form-control" type="text" name="rider_home_banner_title"
                                id="rider_home_banner_title" value="{{ $rider_home_banner_title ?? 'Get 20% Off' }}"
                                placeholder="Get 20% Off">
                        </div>

                        <div class="settings-field settings-field--full">
                            <label for="rider_home_banner_subtitle">Banner subtitle</label>
                            <input class="form-control" type="text" name="rider_home_banner_subtitle"
                                id="rider_home_banner_subtitle" value="{{ $rider_home_banner_subtitle ?? 'Ride across the city with a cleaner, faster booking experience.' }}"
                                placeholder="Ride across the city with a cleaner, faster booking experience.">
                        </div>

                        <div class="settings-field">
                            <label for="rider_home_banner_primary_color">Primary color</label>
                            <input class="form-control" type="text" name="rider_home_banner_primary_color"
                                id="rider_home_banner_primary_color" value="{{ $rider_home_banner_primary_color ?? '#12284A' }}"
                                placeholder="#12284A">
                        </div>

                        <div class="settings-field">
                            <label for="rider_home_banner_secondary_color">Secondary color</label>
                            <input class="form-control" type="text" name="rider_home_banner_secondary_color"
                                id="rider_home_banner_secondary_color" value="{{ $rider_home_banner_secondary_color ?? '#2F66E0' }}"
                                placeholder="#2F66E0">
                        </div>

                        <div class="settings-field">
                            <label for="rider_home_banner_image">Optional banner image</label>
                            <input class="form-control" type="file" name="rider_home_banner_image"
                                id="rider_home_banner_image" accept="image/*">
                            <small class="text-muted">Optional. Leave empty to keep a clean text-only banner.</small>
                        </div>

                        <div class="settings-field">
                            <label>Current banner image</label>
                            <div class="settings-media-preview-wrap">
                                <div class="settings-media-preview" id="rider_home_banner_image_preview">
                                    @if (!empty($rider_home_banner_image))
                                        <img src="{{ $rider_home_banner_image }}" alt="Current banner image">
                                    @else
                                        <span>No banner image uploaded</span>
                                    @endif
                                </div>
                            </div>
                            <label class="settings-inline-checkbox" style="margin-top: 10px;">
                                <input type="checkbox" name="rider_home_banner_image_remove" value="1">
                                <span>Remove current banner image</span>
                            </label>
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
        function setupImagePreview(inputId, previewId, label) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);

            if (!input || !preview) {
                return;
            }

            input.addEventListener('change', function(event) {
                const [file] = event.target.files || [];
                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="${label}">`;
                };
                reader.readAsDataURL(file);
            });
        }

        $(document).ready(function() {
            setupImagePreview('rider_home_banner_image', 'rider_home_banner_image_preview', 'Selected banner image');
            $('#app_settings_form').on('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
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
