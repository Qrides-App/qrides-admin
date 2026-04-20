@extends('layouts.admin')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/driver-profile.css') }}">
@endsection

@section('content')
    <div class="content container-fluid">
        <div class="driver-profile-page">
            <div class="profile-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="driver-header">
                            <div class="title">Add Driver</div>
                            <div class="actions">
                                <a href="{{ route('admin.drivers.index') }}" class="btn btn-gray">Back To Drivers</a>
                            </div>
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

                    <div class="row g-3">
                        <div class="col-md-12">
                            <h3 class="section-title mb-0">Step 1. Driver account details</h3>
                            <p class="text-muted">Create the captain profile, login credentials, and account status used in the admin panel.</p>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required" for="first_name">First Name</label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name"
                                id="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name"
                                id="last_name" value="{{ old('last_name') }}">
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required" for="email">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                                id="email" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required" for="password">Temporary Password</label>
                            <input type="text" class="form-control @error('password') is-invalid @enderror" name="password"
                                id="password" value="{{ old('password') }}" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <div class="row g-2">
                                <div class="col-md-4">
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
                                <div class="col-md-8">
                                    <label class="required" for="phone">Phone Number</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone"
                                        id="phone" value="{{ old('phone') }}" required>
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <input type="hidden" name="default_country" id="default_country" value="{{ old('default_country') }}">
                        </div>

                        <div class="col-md-2 form-group">
                            <label class="required" for="status">Account Status</label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="1" {{ old('status', '1') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-2 form-group">
                            <label class="required" for="document_verify">Document Status</label>
                            <select name="document_verify" id="document_verify" class="form-control @error('document_verify') is-invalid @enderror">
                                <option value="0" {{ old('document_verify', '0') === '0' ? 'selected' : '' }}>Pending Review</option>
                                <option value="1" {{ old('document_verify') === '1' ? 'selected' : '' }}>Approved</option>
                            </select>
                            @error('document_verify')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-2 form-group">
                            <label class="required" for="host_status">Captain Approval</label>
                            <select name="host_status" id="host_status" class="form-control @error('host_status') is-invalid @enderror">
                                <option value="2" {{ old('host_status', '2') === '2' ? 'selected' : '' }}>Requested</option>
                                <option value="1" {{ old('host_status') === '1' ? 'selected' : '' }}>Approved</option>
                                <option value="0" {{ old('host_status') === '0' ? 'selected' : '' }}>Rejected</option>
                            </select>
                            @error('host_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-12 form-group">
                            <label for="profile_image">Profile Image</label>
                            <div class="needsclick dropzone @error('profile_image') is-invalid @enderror" id="profile_image-dropzone"></div>
                            <small class="text-muted">Optional. Used on the driver list and account page.</small>
                            @error('profile_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-4">
                        <div class="col-md-12">
                            <h3 class="section-title mb-0">Step 2. Captain verification documents</h3>
                            <p class="text-muted">This matches the app-side captain verification upload step. These 6 documents are what the app sends for captain approval review.</p>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required">Driving Licence Front</label>
                            <div class="needsclick dropzone @error('driving_licence_front') is-invalid @enderror" id="driving_licence_front-dropzone"></div>
                            @error('driving_licence_front')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required">Driving Licence Back</label>
                            <div class="needsclick dropzone @error('driving_licence_back') is-invalid @enderror" id="driving_licence_back-dropzone"></div>
                            @error('driving_licence_back')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required">Aadhaar Front</label>
                            <div class="needsclick dropzone @error('aadhaar_front') is-invalid @enderror" id="aadhaar_front-dropzone"></div>
                            @error('aadhaar_front')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required">Aadhaar Back</label>
                            <div class="needsclick dropzone @error('aadhaar_back') is-invalid @enderror" id="aadhaar_back-dropzone"></div>
                            @error('aadhaar_back')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required">PAN Card</label>
                            <div class="needsclick dropzone @error('pan_card') is-invalid @enderror" id="pan_card-dropzone"></div>
                            @error('pan_card')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required">Vehicle Insurance</label>
                            <div class="needsclick dropzone @error('vehicle_insurance_doc') is-invalid @enderror" id="vehicle_insurance_doc-dropzone"></div>
                            <small class="text-muted">The app also uploads this in the captain verification document step.</small>
                            @error('vehicle_insurance_doc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-4">
                        <div class="col-md-12">
                            <h3 class="section-title mb-0">Step 3. Vehicle setup and media</h3>
                            <p class="text-muted">This matches the app-side vehicle setup flow. Vehicle image and registration document are uploaded separately from the captain verification docs.</p>
                        </div>

                        <div class="col-md-4 form-group">
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

                        <div class="col-md-4 form-group">
                            <label class="required" for="make">Vehicle Make</label>
                            <select name="make" id="vehicleMakeSelect" class="form-control @error('make') is-invalid @enderror">
                                <option value="">Please select</option>
                            </select>
                            @error('make')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="required" for="model">Vehicle Model</label>
                            <input type="text" name="model" id="model" class="form-control @error('model') is-invalid @enderror"
                                value="{{ old('model') }}">
                            @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
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

                        <div class="col-md-6 form-group">
                            <label class="required" for="registration_number">Vehicle Number</label>
                            <input type="text" name="registration_number" id="registration_number"
                                class="form-control @error('registration_number') is-invalid @enderror"
                                value="{{ old('registration_number') }}">
                            @error('registration_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required">Vehicle Image</label>
                            <div class="needsclick dropzone @error('vehicle_image') is-invalid @enderror" id="vehicle_image-dropzone"></div>
                            @error('vehicle_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="required">Vehicle Registration Document</label>
                            <div class="needsclick dropzone @error('vehicle_registration_doc') is-invalid @enderror" id="vehicle_registration_doc-dropzone"></div>
                            @error('vehicle_registration_doc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>App parity:</strong> the mobile app currently uploads 6 captain verification documents here, and uploads vehicle image plus vehicle registration document during vehicle setup. If you select <em>Captain Approval = Approved</em>, documents must also be marked approved. Otherwise keep the driver in <em>Requested</em> and finish review later from Captain Requests.
                            </div>
                        </div>

                        <div class="col-md-12 form-group">
                            <button class="btn btn-primary btn-lg w-100 py-3" type="submit">Create Driver</button>
                        </div>
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

        function updateDefaultCountry() {
            const dialCode = document.getElementById('phone_country').value;
            const country = @json(config('countries')).find(item => item.dial_code === dialCode);
            if (country) {
                document.getElementById('default_country').value = country.code;
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
                },
                removedfile(file) {
                    file.previewElement.remove();
                    if (file.status !== 'error') {
                        document.getElementById('createDriverForm').querySelector(`input[name="${inputName}"]`)?.remove();
                        this.options.maxFiles++;
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
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateDefaultCountry();
            document.getElementById('phone_country').addEventListener('change', updateDefaultCountry);
            document.getElementById('car_type').addEventListener('change', loadVehicleMake);

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
        });
    </script>
@endsection
