@extends('layouts.admin')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/driver-profile.css') }}">
    <style>
        .driver-onboarding-page {
            max-width: 1240px;
            margin: 0 auto;
        }

        .driver-onboarding-intro {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(280px, 0.9fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .driver-onboarding-hero,
        .driver-onboarding-summary,
        .driver-onboarding-section,
        .driver-onboarding-footer {
            background: #fff;
            border: 1px solid #e5ebf5;
            border-radius: 24px;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.06);
        }

        .driver-onboarding-hero,
        .driver-onboarding-summary {
            padding: 24px 28px;
        }

        .driver-onboarding-hero h2 {
            margin: 10px 0 8px;
            font-size: 34px;
            font-weight: 800;
            color: #16213e;
        }

        .driver-onboarding-hero p,
        .driver-onboarding-summary p,
        .driver-onboarding-section__desc {
            color: #60708f;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }

        .driver-onboarding-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #eef4ff;
            color: #2453d4;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .driver-onboarding-summary h4 {
            margin: 0 0 14px;
            font-size: 18px;
            font-weight: 800;
            color: #16213e;
        }

        .driver-onboarding-summary ul {
            margin: 0;
            padding-left: 18px;
            color: #334155;
        }

        .driver-onboarding-summary li + li {
            margin-top: 8px;
        }

        .driver-onboarding-progress {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .driver-onboarding-progress__card {
            background: #fff;
            border: 1px solid #e5ebf5;
            border-radius: 20px;
            padding: 18px 20px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.05);
        }

        .driver-onboarding-progress__label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #71829f;
        }

        .driver-onboarding-progress__value {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #16213e;
            font-size: 26px;
            font-weight: 800;
        }

        .driver-onboarding-progress__value small {
            font-size: 14px;
            font-weight: 700;
            color: #60708f;
        }

        .driver-onboarding-progress__hint {
            margin-top: 8px;
            color: #60708f;
            font-size: 13px;
            line-height: 1.5;
        }

        .driver-onboarding-section {
            margin-bottom: 22px;
            overflow: hidden;
        }

        .driver-onboarding-section__header {
            padding: 22px 26px 16px;
            border-bottom: 1px solid #eef2f7;
            background: linear-gradient(180deg, rgba(241, 246, 255, 0.7), rgba(255, 255, 255, 0));
        }

        .driver-onboarding-section__step {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #2453d4;
            margin-bottom: 8px;
        }

        .driver-onboarding-section__title {
            margin: 0 0 8px;
            font-size: 26px;
            font-weight: 800;
            color: #16213e;
        }

        .driver-onboarding-section__body {
            padding: 24px 26px 28px;
        }

        .driver-onboarding-grid {
            display: grid;
            gap: 18px;
        }

        .driver-onboarding-grid.account {
            grid-template-columns: repeat(12, minmax(0, 1fr));
        }

        .driver-onboarding-grid.docs,
        .driver-onboarding-grid.vehicle {
            grid-template-columns: repeat(12, minmax(0, 1fr));
        }

        .driver-onboarding-upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .driver-onboarding-col-12 { grid-column: span 12; }
        .driver-onboarding-col-8 { grid-column: span 8; }
        .driver-onboarding-col-6 { grid-column: span 6; }
        .driver-onboarding-col-4 { grid-column: span 4; }
        .driver-onboarding-col-3 { grid-column: span 3; }

        .driver-onboarding-field {
            min-width: 0;
        }

        .driver-onboarding-field label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #4e5d78;
        }

        .driver-onboarding-field .form-control {
            min-height: 50px;
            border-radius: 16px;
        }

        .driver-onboarding-inline {
            display: grid;
            grid-template-columns: minmax(140px, 0.9fr) minmax(0, 1.5fr);
            gap: 12px;
        }

        .driver-onboarding-doc-card {
            border: 1px solid #e6edf8;
            border-radius: 18px;
            padding: 16px;
            background: #fbfdff;
            height: 100%;
        }

        .driver-onboarding-doc-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .driver-onboarding-doc-card__title {
            font-size: 13px;
            font-weight: 800;
            line-height: 1.45;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #16213e;
        }

        .driver-onboarding-doc-card__status {
            flex-shrink: 0;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #7a889f;
            background: #edf1f7;
        }

        .driver-onboarding-doc-card__status.is-ready {
            color: #0f766e;
            background: #dbf5ee;
        }

        .driver-onboarding-doc-card small {
            display: block;
            margin-top: 10px;
            color: #6b7a96;
            line-height: 1.5;
        }

        .driver-onboarding-doc-card .dropzone {
            min-height: 150px;
            border-radius: 18px;
            margin-bottom: 0;
        }

        .driver-onboarding-note {
            border: 1px solid #cfe0ff;
            background: #f3f8ff;
            color: #33507d;
            border-radius: 18px;
            padding: 16px 18px;
            line-height: 1.65;
        }

        .driver-onboarding-approval-help {
            margin-top: 10px;
            font-size: 13px;
            line-height: 1.6;
            color: #60708f;
        }

        .driver-onboarding-approval-help.is-blocked {
            color: #b45309;
        }

        .driver-onboarding-footer {
            position: sticky;
            bottom: 18px;
            z-index: 5;
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(220px, 0.9fr) auto;
            align-items: center;
            gap: 18px;
            padding: 18px 22px;
            margin-top: 6px;
        }

        .driver-onboarding-footer__readiness {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .driver-onboarding-footer__meta {
            color: #5f6f8f;
            line-height: 1.6;
        }

        .driver-onboarding-footer__meta strong {
            display: block;
            color: #16213e;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .driver-onboarding-footer__chip {
            border-radius: 16px;
            background: #f8fbff;
            border: 1px solid #e5ebf5;
            padding: 12px 14px;
        }

        .driver-onboarding-footer__chip strong {
            display: block;
            margin-bottom: 4px;
            color: #16213e;
            font-size: 13px;
        }

        .driver-onboarding-footer__chip span {
            color: #60708f;
            font-size: 13px;
            line-height: 1.5;
        }

        .driver-onboarding-footer .btn {
            min-width: 180px;
            border-radius: 16px;
        }

        @media (max-width: 1199px) {
            .driver-onboarding-intro {
                grid-template-columns: 1fr;
            }

            .driver-onboarding-progress {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .driver-onboarding-col-4,
            .driver-onboarding-col-3 {
                grid-column: span 6;
            }

            .driver-onboarding-footer {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .driver-onboarding-hero,
            .driver-onboarding-summary,
            .driver-onboarding-section__header,
            .driver-onboarding-section__body,
            .driver-onboarding-footer {
                padding: 18px;
            }

            .driver-onboarding-hero h2 {
                font-size: 28px;
            }

            .driver-onboarding-progress {
                grid-template-columns: 1fr;
            }

            .driver-onboarding-col-8,
            .driver-onboarding-col-6,
            .driver-onboarding-col-4,
            .driver-onboarding-col-3 {
                grid-column: span 12;
            }

            .driver-onboarding-inline {
                grid-template-columns: 1fr;
            }

            .driver-onboarding-footer {
                position: static;
                grid-template-columns: 1fr;
            }

            .driver-onboarding-footer__readiness {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="content container-fluid driver-onboarding-page">
        <div class="driver-profile-page">
            <div class="profile-container">
                <div class="driver-onboarding-intro">
                    <div class="driver-onboarding-hero">
                        <span class="driver-onboarding-eyebrow">Driver Management</span>
                        <h2>Add Driver</h2>
                        <p>Create a fully usable captain profile in one pass. This flow collects account details, the 6 app-side captain verification documents, and the separate vehicle setup media so admin can review or approve immediately.</p>
                    </div>
                    <div class="driver-onboarding-summary">
                        <h4>What this creates</h4>
                        <ul>
                            <li>Driver login account with captain approval status</li>
                            <li>App-side captain verification documents with review status</li>
                            <li>Primary vehicle record with year and registration number</li>
                            <li>Vehicle media for admin, driver profile, and future booking use</li>
                        </ul>
                        <div style="margin-top: 18px;">
                            <a href="{{ route('admin.drivers.index') }}" class="btn btn-gray">Back To Drivers</a>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.drivers.store') }}" enctype="multipart/form-data" id="createDriverForm">
                    @csrf
                    @foreach (['profile_image', 'driving_licence_front', 'driving_licence_back', 'aadhaar_front', 'aadhaar_back', 'pan_card', 'vehicle_insurance_doc', 'vehicle_image', 'vehicle_registration_doc'] as $uploadedField)
                        @if (old($uploadedField))
                            <input type="hidden" name="{{ $uploadedField }}" value="{{ old($uploadedField) }}">
                        @endif
                    @endforeach

                    <div class="driver-onboarding-progress">
                        <div class="driver-onboarding-progress__card">
                            <span class="driver-onboarding-progress__label">Account Setup</span>
                            <div class="driver-onboarding-progress__value">
                                <span id="accountReadinessValue">0/5</span>
                                <small>required</small>
                            </div>
                            <div class="driver-onboarding-progress__hint" id="accountReadinessHint">Fill the driver profile, phone, and approval controls.</div>
                        </div>
                        <div class="driver-onboarding-progress__card">
                            <span class="driver-onboarding-progress__label">Captain Docs</span>
                            <div class="driver-onboarding-progress__value">
                                <span id="docReadinessValue">0/6</span>
                                <small>uploaded</small>
                            </div>
                            <div class="driver-onboarding-progress__hint" id="docReadinessHint">All 6 captain documents are needed before approval can be granted.</div>
                        </div>
                        <div class="driver-onboarding-progress__card">
                            <span class="driver-onboarding-progress__label">Vehicle Setup</span>
                            <div class="driver-onboarding-progress__value">
                                <span id="vehicleReadinessValue">0/7</span>
                                <small>complete</small>
                            </div>
                            <div class="driver-onboarding-progress__hint" id="vehicleReadinessHint">Vehicle identity, year, number, and media must be attached.</div>
                        </div>
                        <div class="driver-onboarding-progress__card">
                            <span class="driver-onboarding-progress__label">Approval Gate</span>
                            <div class="driver-onboarding-progress__value">
                                <span id="approvalReadinessValue">Blocked</span>
                            </div>
                            <div class="driver-onboarding-progress__hint" id="approvalReadinessHint">Keep this driver as Requested until docs are uploaded, reviewed, and vehicle setup is complete.</div>
                        </div>
                    </div>

                    <section class="driver-onboarding-section">
                        <div class="driver-onboarding-section__header">
                            <div class="driver-onboarding-section__step">Step 1</div>
                            <h3 class="driver-onboarding-section__title">Driver account details</h3>
                            <p class="driver-onboarding-section__desc">Create the captain profile, temporary login credentials, and approval state that admin will see in the driver list.</p>
                        </div>
                        <div class="driver-onboarding-section__body">
                            <div class="driver-onboarding-grid account">
                        <div class="driver-onboarding-field driver-onboarding-col-6 form-group">
                            <label class="required" for="first_name">First Name</label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name"
                                id="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-6 form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name"
                                id="last_name" value="{{ old('last_name') }}">
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-6 form-group">
                            <label class="required" for="email">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                                id="email" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-6 form-group">
                            <label class="required" for="password">Temporary Password</label>
                            <input type="text" class="form-control @error('password') is-invalid @enderror" name="password"
                                id="password" value="{{ old('password') }}" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-6 form-group">
                            <div class="driver-onboarding-inline">
                                <div>
                                    <label class="required" for="phone_country">Phone Country</label>
                                    <select name="phone_country" id="phone_country" class="form-control @error('phone_country') is-invalid @enderror" onchange="updateDefaultCountry()">
                                        @foreach (config('countries') as $country)
                                            <option value="{{ $country['dial_code'] }}" {{ old('phone_country', '+91') == $country['dial_code'] ? 'selected' : '' }}>
                                                {{ $country['name'] }} ({{ $country['dial_code'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('phone_country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="required" for="phone">Phone Number</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone"
                                        id="phone" value="{{ old('phone') }}" required>
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <input type="hidden" name="default_country" id="default_country" value="{{ old('default_country') }}">
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-3 form-group">
                            <label class="required" for="status">Account Status</label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="1" {{ old('status', '1') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-3 form-group">
                            <label class="required" for="document_verify">Document Status</label>
                            <select name="document_verify" id="document_verify" class="form-control @error('document_verify') is-invalid @enderror">
                                <option value="0" {{ old('document_verify', '0') === '0' ? 'selected' : '' }}>Pending Review</option>
                                <option value="1" {{ old('document_verify') === '1' ? 'selected' : '' }}>Approved</option>
                            </select>
                            @error('document_verify')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-3 form-group">
                            <label class="required" for="host_status">Captain Approval</label>
                            <select name="host_status" id="host_status" class="form-control @error('host_status') is-invalid @enderror">
                                <option value="2" {{ old('host_status', '2') === '2' ? 'selected' : '' }}>Requested</option>
                                <option value="1" {{ old('host_status') === '1' ? 'selected' : '' }}>Approved</option>
                                <option value="0" {{ old('host_status') === '0' ? 'selected' : '' }}>Rejected</option>
                            </select>
                            <div class="driver-onboarding-approval-help" id="approvalHelp">
                                Approval unlocks automatically when required docs are uploaded, document status is approved, and vehicle setup is complete.
                            </div>
                            @error('host_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-12 form-group">
                            <label for="profile_image">Profile Image</label>
                            <div class="needsclick dropzone @error('profile_image') is-invalid @enderror" id="profile_image-dropzone"></div>
                            <small class="text-muted">Optional. Used on the driver list and account page.</small>
                            @error('profile_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                            </div>
                        </div>
                    </section>

                    <section class="driver-onboarding-section">
                        <div class="driver-onboarding-section__header">
                            <div class="driver-onboarding-section__step">Step 2</div>
                            <h3 class="driver-onboarding-section__title">Captain verification documents</h3>
                            <p class="driver-onboarding-section__desc">This section mirrors the app-side captain verification flow. These 6 documents are the ones the app sends for approval review.</p>
                        </div>
                        <div class="driver-onboarding-section__body">
                            <div class="driver-onboarding-upload-grid">

                        <div class="form-group">
                            <div class="driver-onboarding-doc-card">
                            <div class="driver-onboarding-doc-card__header">
                                <div class="driver-onboarding-doc-card__title">Driving Licence Front</div>
                                <span class="driver-onboarding-doc-card__status" data-doc-status="driving_licence_front">Missing</span>
                            </div>
                            <div class="needsclick dropzone @error('driving_licence_front') is-invalid @enderror" id="driving_licence_front-dropzone"></div>
                            @error('driving_licence_front')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="driver-onboarding-doc-card">
                            <div class="driver-onboarding-doc-card__header">
                                <div class="driver-onboarding-doc-card__title">Driving Licence Back</div>
                                <span class="driver-onboarding-doc-card__status" data-doc-status="driving_licence_back">Missing</span>
                            </div>
                            <div class="needsclick dropzone @error('driving_licence_back') is-invalid @enderror" id="driving_licence_back-dropzone"></div>
                            @error('driving_licence_back')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="driver-onboarding-doc-card">
                            <div class="driver-onboarding-doc-card__header">
                                <div class="driver-onboarding-doc-card__title">Aadhaar Front</div>
                                <span class="driver-onboarding-doc-card__status" data-doc-status="aadhaar_front">Missing</span>
                            </div>
                            <div class="needsclick dropzone @error('aadhaar_front') is-invalid @enderror" id="aadhaar_front-dropzone"></div>
                            @error('aadhaar_front')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="driver-onboarding-doc-card">
                            <div class="driver-onboarding-doc-card__header">
                                <div class="driver-onboarding-doc-card__title">Aadhaar Back</div>
                                <span class="driver-onboarding-doc-card__status" data-doc-status="aadhaar_back">Missing</span>
                            </div>
                            <div class="needsclick dropzone @error('aadhaar_back') is-invalid @enderror" id="aadhaar_back-dropzone"></div>
                            @error('aadhaar_back')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="driver-onboarding-doc-card">
                            <div class="driver-onboarding-doc-card__header">
                                <div class="driver-onboarding-doc-card__title">PAN Card</div>
                                <span class="driver-onboarding-doc-card__status" data-doc-status="pan_card">Missing</span>
                            </div>
                            <div class="needsclick dropzone @error('pan_card') is-invalid @enderror" id="pan_card-dropzone"></div>
                            @error('pan_card')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="driver-onboarding-doc-card">
                            <div class="driver-onboarding-doc-card__header">
                                <div class="driver-onboarding-doc-card__title">Vehicle Insurance</div>
                                <span class="driver-onboarding-doc-card__status" data-doc-status="vehicle_insurance_doc">Missing</span>
                            </div>
                            <div class="needsclick dropzone @error('vehicle_insurance_doc') is-invalid @enderror" id="vehicle_insurance_doc-dropzone"></div>
                            <small class="text-muted">The app also uploads this in the captain verification document step.</small>
                            @error('vehicle_insurance_doc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                            </div>
                        </div>
                    </section>

                    <section class="driver-onboarding-section">
                        <div class="driver-onboarding-section__header">
                            <div class="driver-onboarding-section__step">Step 3</div>
                            <h3 class="driver-onboarding-section__title">Vehicle setup and media</h3>
                            <p class="driver-onboarding-section__desc">This matches the app-side vehicle setup flow. Vehicle image and registration document are uploaded separately from the captain verification documents.</p>
                        </div>
                        <div class="driver-onboarding-section__body">
                            <div class="driver-onboarding-grid vehicle">

                        <div class="driver-onboarding-field driver-onboarding-col-3 form-group">
                            <label class="required" for="car_type">Vehicle Type</label>
                            <select name="car_type" id="car_type" class="form-control @error('car_type') is-invalid @enderror">
                                <option value="">Please select</option>
                                @foreach ($vehicleTypes as $vehicleType)
                                    <option value="{{ $vehicleType->id }}" {{ old('car_type') == $vehicleType->id ? 'selected' : '' }}>
                                        {{ $vehicleType->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('car_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-3 form-group">
                            <label class="required" for="make">Vehicle Make</label>
                            <select name="make" id="vehicleMakeSelect" class="form-control @error('make') is-invalid @enderror">
                                <option value="">Please select</option>
                            </select>
                            @error('make')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-3 form-group">
                            <label class="required" for="model">Vehicle Model</label>
                            <input type="text" name="model" id="model" class="form-control @error('model') is-invalid @enderror"
                                value="{{ old('model') }}">
                            @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-3 form-group">
                            <label class="required" for="year">Vehicle Year</label>
                            <select name="year" id="year" class="form-control @error('year') is-invalid @enderror">
                                <option value="">Please select</option>
                                @php $currentYear = date('Y'); @endphp
                                @for ($year = $currentYear; $year >= $currentYear - 30; $year--)
                                    <option value="{{ $year }}" {{ old('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-field driver-onboarding-col-4 form-group">
                            <label class="required" for="registration_number">Vehicle Number</label>
                            <input type="text" name="registration_number" id="registration_number"
                                class="form-control @error('registration_number') is-invalid @enderror"
                                value="{{ old('registration_number') }}">
                            @error('registration_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="driver-onboarding-col-4 form-group">
                            <div class="driver-onboarding-doc-card">
                            <div class="driver-onboarding-doc-card__header">
                                <div class="driver-onboarding-doc-card__title">Vehicle Image</div>
                                <span class="driver-onboarding-doc-card__status" data-doc-status="vehicle_image">Missing</span>
                            </div>
                            <div class="needsclick dropzone @error('vehicle_image') is-invalid @enderror" id="vehicle_image-dropzone"></div>
                            @error('vehicle_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="driver-onboarding-col-4 form-group">
                            <div class="driver-onboarding-doc-card">
                            <div class="driver-onboarding-doc-card__header">
                                <div class="driver-onboarding-doc-card__title">Vehicle Registration Document</div>
                                <span class="driver-onboarding-doc-card__status" data-doc-status="vehicle_registration_doc">Missing</span>
                            </div>
                            <div class="needsclick dropzone @error('vehicle_registration_doc') is-invalid @enderror" id="vehicle_registration_doc-dropzone"></div>
                            @error('vehicle_registration_doc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="driver-onboarding-col-12">
                            <div class="driver-onboarding-note">
                                <strong>App parity:</strong> the mobile app currently uploads 6 captain verification documents here, and uploads vehicle image plus vehicle registration document during vehicle setup. If you select <em>Captain Approval = Approved</em>, documents must also be marked approved. Otherwise keep the driver in <em>Requested</em> and finish review later from Captain Requests.
                            </div>
                        </div>
                        </div>
                    </section>

                    <div class="driver-onboarding-footer">
                        <div class="driver-onboarding-footer__meta">
                            <strong>Ready to create the driver?</strong>
                            This will create the driver account, attach the captain documents, create the primary vehicle, and place the driver into the approval state you selected above.
                        </div>
                        <div class="driver-onboarding-footer__readiness">
                            <div class="driver-onboarding-footer__chip">
                                <strong>Captain docs</strong>
                                <span id="footerDocsReadiness">0 of 6 uploaded</span>
                            </div>
                            <div class="driver-onboarding-footer__chip">
                                <strong>Vehicle setup</strong>
                                <span id="footerVehicleReadiness">0 of 7 complete</span>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-lg" type="submit">Create Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script>
        Dropzone.autoDiscover = false;
        const requiredDocFields = [
            'driving_licence_front',
            'driving_licence_back',
            'aadhaar_front',
            'aadhaar_back',
            'pan_card',
            'vehicle_insurance_doc'
        ];
        const requiredVehicleFields = [
            'car_type',
            'make',
            'model',
            'year',
            'registration_number',
            'vehicle_image',
            'vehicle_registration_doc'
        ];

        function updateDefaultCountry() {
            const dialCode = document.getElementById('phone_country').value;
            const country = @json(config('countries')).find(item => item.dial_code === dialCode);
            if (country) {
                document.getElementById('default_country').value = country.code;
            }
        }

        function hasHiddenUpload(fieldName) {
            return Boolean(document.querySelector(`#createDriverForm input[name="${fieldName}"]`));
        }

        function setUploadStatus(fieldName, ready) {
            const badge = document.querySelector(`[data-doc-status="${fieldName}"]`);
            if (!badge) {
                return;
            }

            badge.textContent = ready ? 'Ready' : 'Missing';
            badge.classList.toggle('is-ready', ready);
        }

        function countCompletedFields(fieldNames) {
            return fieldNames.filter(function (field) {
                const input = document.getElementById(field) || document.getElementsByName(field)[0];
                if (!input) {
                    return hasHiddenUpload(field);
                }

                return String(input.value || '').trim() !== '';
            }).length;
        }

        function syncDriverReadiness() {
            requiredDocFields.concat(['vehicle_image', 'vehicle_registration_doc']).forEach(function (field) {
                setUploadStatus(field, hasHiddenUpload(field));
            });

            const accountFields = ['first_name', 'email', 'password', 'phone', 'phone_country'];
            const accountComplete = countCompletedFields(accountFields);
            const docComplete = requiredDocFields.filter(hasHiddenUpload).length;
            const vehicleComplete = countCompletedFields(requiredVehicleFields);
            const docsApproved = document.getElementById('document_verify').value === '1';
            const vehicleReady = vehicleComplete === requiredVehicleFields.length;
            const approvalReady = docComplete === requiredDocFields.length && docsApproved && vehicleReady;

            document.getElementById('accountReadinessValue').textContent = `${accountComplete}/${accountFields.length}`;
            document.getElementById('accountReadinessHint').textContent = accountComplete === accountFields.length
                ? 'Account identity and contact details are complete.'
                : 'Complete first name, email, temporary password, phone country, and phone number.';

            document.getElementById('docReadinessValue').textContent = `${docComplete}/${requiredDocFields.length}`;
            document.getElementById('docReadinessHint').textContent = docsApproved
                ? 'Document review is marked approved.'
                : 'Upload all 6 captain documents and switch Document Status to Approved after review.';

            document.getElementById('vehicleReadinessValue').textContent = `${vehicleComplete}/${requiredVehicleFields.length}`;
            document.getElementById('vehicleReadinessHint').textContent = vehicleReady
                ? 'Vehicle identity and media are complete.'
                : 'Vehicle type, make, model, year, number, image, and registration document are required.';

            document.getElementById('footerDocsReadiness').textContent = `${docComplete} of ${requiredDocFields.length} uploaded`;
            document.getElementById('footerVehicleReadiness').textContent = `${vehicleComplete} of ${requiredVehicleFields.length} complete`;

            const hostStatus = document.getElementById('host_status');
            const approvedOption = hostStatus.querySelector('option[value="1"]');
            const approvalReadinessValue = document.getElementById('approvalReadinessValue');
            const approvalReadinessHint = document.getElementById('approvalReadinessHint');
            const approvalHelp = document.getElementById('approvalHelp');

            approvedOption.disabled = !approvalReady;

            if (!approvalReady && hostStatus.value === '1') {
                hostStatus.value = '2';
            }

            if (approvalReady) {
                approvalReadinessValue.textContent = 'Ready';
                approvalReadinessHint.textContent = 'All required docs are uploaded, marked approved, and vehicle setup is complete.';
                approvalHelp.textContent = 'Captain Approval can now be set to Approved.';
                approvalHelp.classList.remove('is-blocked');
            } else {
                approvalReadinessValue.textContent = 'Blocked';
                approvalReadinessHint.textContent = 'Approval stays blocked until 6 captain docs are uploaded, Document Status is Approved, and vehicle setup is complete.';
                approvalHelp.textContent = 'Approved is locked until document uploads, document approval, and vehicle setup are all complete.';
                approvalHelp.classList.add('is-blocked');
            }
        }

        function initDropzone(selector, inputName, options = {}) {
            const defaults = {
                url: '{{ route('admin.app-users.storeMedia') }}',
                maxFilesize: 4,
                maxFiles: 1,
                addRemoveLinks: true,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                params: { size: 4 },
                success(file, response) {
                    const form = document.getElementById('createDriverForm');
                    form.querySelector(`input[name="${inputName}"]`)?.remove();
                    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="${inputName}" value="${response.name}">`);
                    syncDriverReadiness();
                },
                removedfile(file) {
                    file.previewElement.remove();
                    if (file.status !== 'error') {
                        document.getElementById('createDriverForm').querySelector(`input[name="${inputName}"]`)?.remove();
                        this.options.maxFiles++;
                        syncDriverReadiness();
                    }
                },
                error(file, response) {
                    const message = typeof response === 'string' ? response : (response.errors?.file || 'Upload failed');
                    file.previewElement.classList.add('dz-error');
                    file.previewElement.querySelector('[data-dz-errormessage]').textContent = message;
                }
            };

            new Dropzone(selector, Object.assign(defaults, options));
        }

        function loadVehicleMake() {
            const typeId = document.getElementById('car_type').value;
            const makeSelect = document.getElementById('vehicleMakeSelect');
            const selectedMake = @json(old('make'));

            makeSelect.innerHTML = '<option value="">Please select</option>';

            if (!typeId) {
                return;
            }

            $.ajax({
                url: '{{ route('admin.vehicles.get-vehiclemake') }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { typeId: typeId },
                success: function (response) {
                    response.forEach(function (item) {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.name;
                        if (String(selectedMake) === String(item.id)) {
                            option.selected = true;
                        }
                        makeSelect.appendChild(option);
                    });
                    syncDriverReadiness();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateDefaultCountry();
            document.getElementById('phone_country').addEventListener('change', updateDefaultCountry);
            document.getElementById('car_type').addEventListener('change', loadVehicleMake);
            document.querySelectorAll('#createDriverForm input, #createDriverForm select').forEach(function (element) {
                element.addEventListener('change', syncDriverReadiness);
                element.addEventListener('input', syncDriverReadiness);
            });

            initDropzone('#profile_image-dropzone', 'profile_image', {
                acceptedFiles: 'image/jpeg,image/png,image/gif',
                params: { size: 2, width: 4096, height: 4096 }
            });

            initDropzone('#vehicle_image-dropzone', 'vehicle_image', {
                acceptedFiles: 'image/jpeg,image/png,image/gif',
                params: { size: 3, width: 4096, height: 4096 }
            });

            [
                'driving_licence_front',
                'driving_licence_back',
                'aadhaar_front',
                'aadhaar_back',
                'pan_card',
                'vehicle_insurance_doc',
                'vehicle_registration_doc'
            ].forEach(function (field) {
                initDropzone(`#${field}-dropzone`, field, {
                    acceptedFiles: '.jpeg,.jpg,.png,.pdf',
                    params: { size: 4 }
                });
            });

            loadVehicleMake();
            syncDriverReadiness();
        });
    </script>
@endsection
