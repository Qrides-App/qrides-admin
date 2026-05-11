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
                            <p>Control the rider app promo banner with editable copy, live color picking, and preview before saving.</p>
                        </div>
                    </div>

                    <div class="settings-callout settings-callout--soft">
                        <div>
                            Pick the rider app brand colors, check the phone preview, and upload an optional visual only if the banner needs it.
                        </div>
                    </div>

                    <div class="settings-form-grid settings-form-grid--banner">
                        <div class="settings-field">
                            <label for="rider_app_primary_color">App primary color</label>
                            <div class="settings-color-control">
                                <input class="settings-color-control__picker" type="color"
                                    id="rider_app_primary_color_picker"
                                    value="{{ $rider_app_primary_color ?? '#3E6BCB' }}"
                                    aria-label="App primary color picker">
                                <input class="form-control settings-color-control__hex" type="text"
                                    name="rider_app_primary_color" id="rider_app_primary_color"
                                    value="{{ $rider_app_primary_color ?? '#3E6BCB' }}"
                                    placeholder="#3E6BCB" maxlength="7">
                            </div>
                            <small class="text-muted">Used for buttons, active highlights, and primary rider app emphasis.</small>
                        </div>

                        <div class="settings-field">
                            <label for="rider_app_accent_color">App accent color</label>
                            <div class="settings-color-control">
                                <input class="settings-color-control__picker" type="color"
                                    id="rider_app_accent_color_picker"
                                    value="{{ $rider_app_accent_color ?? '#3ECF8E' }}"
                                    aria-label="App accent color picker">
                                <input class="form-control settings-color-control__hex" type="text"
                                    name="rider_app_accent_color" id="rider_app_accent_color"
                                    value="{{ $rider_app_accent_color ?? '#3ECF8E' }}"
                                    placeholder="#3ECF8E" maxlength="7">
                            </div>
                            <small class="text-muted">Used for secondary success-style highlights across the rider app.</small>
                        </div>

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
                            <div class="settings-color-control">
                                <input class="settings-color-control__picker" type="color"
                                    id="rider_home_banner_primary_color_picker"
                                    value="{{ $rider_home_banner_primary_color ?? '#12284A' }}"
                                    aria-label="Primary color picker">
                                <input class="form-control settings-color-control__hex" type="text"
                                    name="rider_home_banner_primary_color" id="rider_home_banner_primary_color"
                                    value="{{ $rider_home_banner_primary_color ?? '#12284A' }}"
                                    placeholder="#12284A" maxlength="7">
                            </div>
                            <small class="text-muted">Used for the deeper side of the banner gradient.</small>
                        </div>

                        <div class="settings-field">
                            <label for="rider_home_banner_secondary_color">Secondary color</label>
                            <div class="settings-color-control">
                                <input class="settings-color-control__picker" type="color"
                                    id="rider_home_banner_secondary_color_picker"
                                    value="{{ $rider_home_banner_secondary_color ?? '#2F66E0' }}"
                                    aria-label="Secondary color picker">
                                <input class="form-control settings-color-control__hex" type="text"
                                    name="rider_home_banner_secondary_color" id="rider_home_banner_secondary_color"
                                    value="{{ $rider_home_banner_secondary_color ?? '#2F66E0' }}"
                                    placeholder="#2F66E0" maxlength="7">
                            </div>
                            <small class="text-muted">Used for the brighter highlight side of the gradient.</small>
                        </div>

                        <div class="settings-field settings-field--full">
                            <label>Quick color presets</label>
                            <div class="settings-color-presets">
                                <button type="button" class="settings-color-preset" data-primary="#12284A" data-secondary="#2F66E0" data-app-primary="#3E6BCB" data-app-accent="#3ECF8E">
                                    <span class="settings-color-preset__swatches"><i style="background:#12284A;"></i><i style="background:#2F66E0;"></i></span>
                                    <span>Navy</span>
                                </button>
                                <button type="button" class="settings-color-preset" data-primary="#0F766E" data-secondary="#14B8A6" data-app-primary="#0F766E" data-app-accent="#2DD4BF">
                                    <span class="settings-color-preset__swatches"><i style="background:#0F766E;"></i><i style="background:#14B8A6;"></i></span>
                                    <span>Teal</span>
                                </button>
                                <button type="button" class="settings-color-preset" data-primary="#7C2D12" data-secondary="#F97316" data-app-primary="#C2410C" data-app-accent="#FB923C">
                                    <span class="settings-color-preset__swatches"><i style="background:#7C2D12;"></i><i style="background:#F97316;"></i></span>
                                    <span>Sunset</span>
                                </button>
                                <button type="button" class="settings-color-preset" data-primary="#3B0764" data-secondary="#C026D3" data-app-primary="#7E22CE" data-app-accent="#E879F9">
                                    <span class="settings-color-preset__swatches"><i style="background:#3B0764;"></i><i style="background:#C026D3;"></i></span>
                                    <span>Plum</span>
                                </button>
                            </div>
                        </div>

                        <div class="settings-field settings-field--full">
                            <div class="settings-app-preview-shell">
                                <div class="settings-app-preview-phone" id="rider_app_preview"
                                    style="--banner-primary: {{ $rider_home_banner_primary_color ?? '#12284A' }}; --banner-secondary: {{ $rider_home_banner_secondary_color ?? '#2F66E0' }}; --app-primary: {{ $rider_app_primary_color ?? '#3E6BCB' }}; --app-accent: {{ $rider_app_accent_color ?? '#3ECF8E' }};">
                                    <div class="settings-app-preview-phone__status"></div>
                                    <div class="settings-app-preview-phone__map"></div>
                                    <div class="settings-app-preview-phone__sheet">
                                        <div class="settings-app-preview-phone__topline">
                                            <span class="settings-app-preview-phone__handle"></span>
                                        </div>
                                        <div class="settings-banner-preview settings-banner-preview--inside-app" id="rider_home_banner_preview">
                                            <div class="settings-banner-preview__content">
                                                <span class="settings-banner-preview__eyebrow" id="rider_home_banner_preview_eyebrow">{{ $rider_home_banner_eyebrow ?? 'First ride offer' }}</span>
                                                <h4 id="rider_home_banner_preview_title">{{ $rider_home_banner_title ?? 'Get 20% Off' }}</h4>
                                                <p id="rider_home_banner_preview_subtitle">{{ $rider_home_banner_subtitle ?? 'Ride across the city with a cleaner, faster booking experience.' }}</p>
                                            </div>
                                            <div class="settings-banner-preview__visual" id="rider_home_banner_preview_visual">
                                                @if (!empty($rider_home_banner_image))
                                                    <img src="{{ $rider_home_banner_image }}" alt="Banner preview image">
                                                @else
                                                    <div class="settings-banner-preview__placeholder">
                                                        <span></span>
                                                        <span></span>
                                                        <span></span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="settings-app-preview-phone__actions">
                                            <span class="settings-app-preview-phone__chip">Ride</span>
                                            <span class="settings-app-preview-phone__chip settings-app-preview-phone__chip--accent">Nearby</span>
                                        </div>
                                        <button type="button" class="settings-app-preview-phone__button">Book now</button>
                                    </div>
                                </div>
                                <div class="settings-app-preview-copy">
                                    <h5>Live app preview</h5>
                                    <p>Shows the rider home banner inside an app-style sheet, along with the current primary button and accent chip colors.</p>
                                </div>
                            </div>
                            <small class="text-muted">Live preview of how the rider home banner and app theme colors will feel together.</small>
                        </div>

                        <div class="settings-field">
                            <label for="rider_home_banner_image">Optional banner image</label>
                            <input class="form-control" type="file" name="rider_home_banner_image"
                                id="rider_home_banner_image" accept="image/*">
                            <small class="text-muted">Optional. Best result is a square PNG with simple artwork and no text inside the image.</small>
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

                    <div class="settings-card__footer settings-actions">
                        <button type="button" class="btn settings-btn-secondary" id="rider_home_banner_reset_defaults">
                            Reset to defaults
                        </button>
                        <button type="submit" class="btn btn-primary btn-space">{{ trans('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        function normaliseHexColor(value, fallback = '#12284A') {
            const rawValue = (value || '').trim();
            const withoutHash = rawValue.replace(/^#/, '');
            if (/^[0-9A-Fa-f]{6}$/.test(withoutHash)) {
                return `#${withoutHash.toUpperCase()}`;
            }

            return fallback;
        }

        function hexToRgba(hexValue, alpha, fallback) {
            const normalized = normaliseHexColor(hexValue, fallback);
            const hex = normalized.replace('#', '');
            const red = Number.parseInt(hex.slice(0, 2), 16);
            const green = Number.parseInt(hex.slice(2, 4), 16);
            const blue = Number.parseInt(hex.slice(4, 6), 16);

            return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
        }

        function darkenHexColor(hexValue, amount, fallback) {
            const normalized = normaliseHexColor(hexValue, fallback);
            const hex = normalized.replace('#', '');
            const clampChannel = function(channel) {
                return Math.max(0, Math.min(255, channel - amount));
            };

            const red = clampChannel(Number.parseInt(hex.slice(0, 2), 16));
            const green = clampChannel(Number.parseInt(hex.slice(2, 4), 16));
            const blue = clampChannel(Number.parseInt(hex.slice(4, 6), 16));

            return `rgb(${red}, ${green}, ${blue})`;
        }

        function setupImagePreview(inputId, previewId, label) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const bannerVisual = document.getElementById('rider_home_banner_preview_visual');

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
                    if (bannerVisual) {
                        bannerVisual.innerHTML = `<img src="${e.target.result}" alt="${label}">`;
                    }
                };
                reader.readAsDataURL(file);
            });
        }

        function setupBannerColorControl(textInputId, colorInputId, cssVariable, fallback) {
            const textInput = document.getElementById(textInputId);
            const colorInput = document.getElementById(colorInputId);
            const preview = document.getElementById('rider_app_preview');

            if (!textInput || !colorInput || !preview) {
                return;
            }

            const applyColor = function(value) {
                const normalized = normaliseHexColor(value, fallback);
                textInput.value = normalized;
                colorInput.value = normalized;
                preview.style.setProperty(cssVariable, normalized);
                if (cssVariable === '--app-primary') {
                    preview.style.setProperty('--app-primary-soft', hexToRgba(normalized, 0.12, fallback));
                    preview.style.setProperty('--app-primary-shadow', hexToRgba(normalized, 0.34, fallback));
                }
                if (cssVariable === '--app-accent') {
                    preview.style.setProperty('--app-accent-soft', hexToRgba(normalized, 0.18, fallback));
                    preview.style.setProperty('--app-accent-strong', darkenHexColor(normalized, 70, fallback));
                }
            };

            applyColor(textInput.value || fallback);

            colorInput.addEventListener('input', function(event) {
                applyColor(event.target.value);
            });

            textInput.addEventListener('input', function(event) {
                const candidate = event.target.value.trim();
                if (/^#?[0-9A-Fa-f]{6}$/.test(candidate)) {
                    applyColor(candidate);
                }
            });

            textInput.addEventListener('blur', function(event) {
                applyColor(event.target.value || fallback);
            });
        }

        function setupBannerTextPreview(inputId, previewId, fallback) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);

            if (!input || !preview) {
                return;
            }

            const sync = function(value) {
                preview.textContent = value.trim() || fallback;
            };

            sync(input.value || fallback);
            input.addEventListener('input', function(event) {
                sync(event.target.value);
            });
        }

        function renderBannerPlaceholder() {
            return `
                <div class="settings-banner-preview__placeholder">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `;
        }

        function resetRiderBannerDefaults() {
            const defaultValues = {
                rider_app_primary_color: '#3E6BCB',
                rider_app_accent_color: '#3ECF8E',
                rider_home_banner_eyebrow: 'First ride offer',
                rider_home_banner_title: 'Get 20% Off',
                rider_home_banner_subtitle: 'Ride across the city with a cleaner, faster booking experience.',
                rider_home_banner_primary_color: '#12284A',
                rider_home_banner_secondary_color: '#2F66E0',
            };

            Object.entries(defaultValues).forEach(([fieldId, value]) => {
                const field = document.getElementById(fieldId);
                if (!field) {
                    return;
                }
                field.value = value;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('blur', { bubbles: true }));
            });

            const imageInput = document.getElementById('rider_home_banner_image');
            if (imageInput) {
                imageInput.value = '';
            }

            const removeCheckbox = document.querySelector('input[name="rider_home_banner_image_remove"]');
            if (removeCheckbox) {
                removeCheckbox.checked = true;
            }

            const currentPreview = document.getElementById('rider_home_banner_image_preview');
            if (currentPreview) {
                currentPreview.innerHTML = '<span>No banner image uploaded</span>';
            }

            const bannerVisual = document.getElementById('rider_home_banner_preview_visual');
            if (bannerVisual) {
                bannerVisual.innerHTML = renderBannerPlaceholder();
            }
        }

        $(document).ready(function() {
            setupImagePreview('rider_home_banner_image', 'rider_home_banner_image_preview', 'Selected banner image');
            setupBannerColorControl('rider_app_primary_color', 'rider_app_primary_color_picker', '--app-primary', '#3E6BCB');
            setupBannerColorControl('rider_app_accent_color', 'rider_app_accent_color_picker', '--app-accent', '#3ECF8E');
            setupBannerColorControl('rider_home_banner_primary_color', 'rider_home_banner_primary_color_picker', '--banner-primary', '#12284A');
            setupBannerColorControl('rider_home_banner_secondary_color', 'rider_home_banner_secondary_color_picker', '--banner-secondary', '#2F66E0');
            setupBannerTextPreview('rider_home_banner_eyebrow', 'rider_home_banner_preview_eyebrow', 'First ride offer');
            setupBannerTextPreview('rider_home_banner_title', 'rider_home_banner_preview_title', 'Get 20% Off');
            setupBannerTextPreview('rider_home_banner_subtitle', 'rider_home_banner_preview_subtitle', 'Ride across the city with a cleaner, faster booking experience.');

            $('.settings-color-preset').on('click', function() {
                const primary = $(this).data('primary');
                const secondary = $(this).data('secondary');
                const appPrimary = $(this).data('app-primary');
                const appAccent = $(this).data('app-accent');

                $('#rider_home_banner_primary_color').val(primary).trigger('blur');
                $('#rider_home_banner_secondary_color').val(secondary).trigger('blur');
                $('#rider_app_primary_color').val(appPrimary).trigger('blur');
                $('#rider_app_accent_color').val(appAccent).trigger('blur');
            });

            $('input[name="rider_home_banner_image_remove"]').on('change', function() {
                if (!this.checked) {
                    return;
                }

                $('#rider_home_banner_preview_visual').html(renderBannerPlaceholder());
            });

            $('#rider_home_banner_reset_defaults').on('click', function() {
                resetRiderBannerDefaults();
            });

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
