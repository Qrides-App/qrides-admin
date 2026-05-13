@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <strong>{{ trans('global.create') }} {{ trans('global.addCoupon_title_singular') }}</strong>
                            <div class="text-muted small">Build a rider-facing offer with clear code, usage rules, and first-booking targeting.</div>
                        </div>
                        <a href="{{ route('admin.add-coupons.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Coupons
                        </a>
                    </div>

                    <div class="panel-body">
                        <form method="POST" action="{{ route('admin.add-coupons.store') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="module" value="{{ $currentModule->id ?? 1 }}">

                            <div class="row">
                                <div class="col-md-7">
                                    <div class="panel panel-default">
                                        <div class="panel-heading"><strong>Offer Identity</strong></div>
                                        <div class="panel-body">
                                            <div class="form-group {{ $errors->has('coupon_title') ? 'has-error' : '' }}">
                                                <label class="required" for="coupon_title">{{ trans('global.coupon_titles') }}</label>
                                                <input class="form-control" type="text" name="coupon_title" id="coupon_title"
                                                    value="{{ old('coupon_title', '') }}" maxlength="255" required>
                                                <small class="text-muted">Admin-facing title for quickly recognizing the offer.</small>
                                                @if ($errors->has('coupon_title'))
                                                    <span class="help-block">{{ $errors->first('coupon_title') }}</span>
                                                @endif
                                            </div>

                                            <div class="form-group {{ $errors->has('coupon_subtitle') ? 'has-error' : '' }}">
                                                <label for="coupon_subtitle">{{ trans('global.coupon_subtitle') }}</label>
                                                <input class="form-control" type="text" name="coupon_subtitle" id="coupon_subtitle"
                                                    value="{{ old('coupon_subtitle', '') }}" maxlength="255">
                                                <small class="text-muted">Short rider-facing support text such as “For first city ride”.</small>
                                                @if ($errors->has('coupon_subtitle'))
                                                    <span class="help-block">{{ $errors->first('coupon_subtitle') }}</span>
                                                @endif
                                            </div>

                                            <div class="form-group {{ $errors->has('coupon_description') ? 'has-error' : '' }}">
                                                <label for="coupon_description">{{ trans('global.coupon_description') }}</label>
                                                <textarea class="form-control" name="coupon_description" id="coupon_description" rows="4"
                                                    placeholder="Explain who should use this coupon and any important internal note.">{{ old('coupon_description', '') }}</textarea>
                                                @if ($errors->has('coupon_description'))
                                                    <span class="help-block">{{ $errors->first('coupon_description') }}</span>
                                                @endif
                                            </div>

                                            <div class="form-group {{ $errors->has('coupon_image_file') ? 'has-error' : '' }}">
                                                <label for="coupon_image_file">{{ trans('global.coupon_image') }}</label>
                                                <input class="form-control" type="file" name="coupon_image_file" id="coupon_image_file" accept=".jpg,.jpeg,.png,.webp">
                                                <small class="text-muted">Optional promo visual. Best result is a compact banner-style asset under 2MB.</small>
                                                @if ($errors->has('coupon_image_file'))
                                                    <span class="help-block">{{ $errors->first('coupon_image_file') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="panel panel-default">
                                        <div class="panel-heading"><strong>Code and Value</strong></div>
                                        <div class="panel-body">
                                            <div class="form-group {{ $errors->has('coupon_code') ? 'has-error' : '' }}">
                                                <label class="required" for="coupon_code">
                                                    {{ trans('global.coupon_code') }}
                                                </label>
                                                <div class="input-group">
                                                    <input class="form-control" type="text" name="coupon_code" id="coupon_code"
                                                        value="{{ old('coupon_code', '') }}" maxlength="50" required>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default" type="button" id="generateCouponButton">Generate</button>
                                                    </span>
                                                </div>
                                                <small class="text-muted">Use a clean uppercase code riders can type easily.</small>
                                                @if ($errors->has('coupon_code'))
                                                    <span class="help-block">{{ $errors->first('coupon_code') }}</span>
                                                @endif
                                            </div>

                                            <div class="row">
                                                <div class="col-sm-7">
                                                    <div class="form-group {{ $errors->has('coupon_value') ? 'has-error' : '' }}">
                                                        <label class="required" for="coupon_value">{{ trans('global.coupon_value') }}</label>
                                                        <input class="form-control" type="number" name="coupon_value" id="coupon_value"
                                                            value="{{ old('coupon_value', '') }}" min="0" max="99" step="0.01" required>
                                                        @if ($errors->has('coupon_value'))
                                                            <span class="help-block">{{ $errors->first('coupon_value') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-sm-5">
                                                    <div class="form-group {{ $errors->has('coupon_type') ? 'has-error' : '' }}">
                                                        <label class="required" for="coupon_type">{{ trans('global.coupon_type') }}</label>
                                                        <select class="form-control" name="coupon_type" id="coupon_type" required>
                                                            <option value="percentage" {{ old('coupon_type', 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                                        </select>
                                                        @if ($errors->has('coupon_type'))
                                                            <span class="help-block">{{ $errors->first('coupon_type') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group {{ $errors->has('min_order_amount') ? 'has-error' : '' }}">
                                                <label for="min_order_amount">{{ trans('global.min_order_amount') }}</label>
                                                <input class="form-control" type="number" name="min_order_amount" id="min_order_amount"
                                                    value="{{ old('min_order_amount', '') }}" step="0.01" min="0">
                                                <small class="text-muted">Leave blank if the coupon should work on any eligible ride fare.</small>
                                                @if ($errors->has('min_order_amount'))
                                                    <span class="help-block">{{ $errors->first('min_order_amount') }}</span>
                                                @endif
                                            </div>

                                            <div class="form-group {{ $errors->has('coupon_expiry_date') ? 'has-error' : '' }}">
                                                <label for="coupon_expiry_date">{{ trans('global.coupon_expiry_date') }}</label>
                                                <input class="form-control" type="date" name="coupon_expiry_date" id="coupon_expiry_date"
                                                    value="{{ old('coupon_expiry_date') }}" min="{{ now()->toDateString() }}">
                                                @if ($errors->has('coupon_expiry_date'))
                                                    <span class="help-block">{{ $errors->first('coupon_expiry_date') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="panel panel-default">
                                <div class="panel-heading"><strong>Usage Rules and Publishing</strong></div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group {{ $errors->has('max_uses') ? 'has-error' : '' }}">
                                                <label for="max_uses">Maximum Uses</label>
                                                <input class="form-control" type="number" name="max_uses" id="max_uses"
                                                    value="{{ old('max_uses', '') }}" min="1">
                                                <small class="text-muted">Blank means unlimited total uses.</small>
                                                @if ($errors->has('max_uses'))
                                                    <span class="help-block">{{ $errors->first('max_uses') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group {{ $errors->has('max_uses_per_user') ? 'has-error' : '' }}">
                                                <label for="max_uses_per_user">Maximum Uses Per User</label>
                                                <input class="form-control" type="number" name="max_uses_per_user" id="max_uses_per_user"
                                                    value="{{ old('max_uses_per_user', '') }}" min="1">
                                                <small class="text-muted">Blank means no per-user cap.</small>
                                                @if ($errors->has('max_uses_per_user'))
                                                    <span class="help-block">{{ $errors->first('max_uses_per_user') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group {{ $errors->has('status') ? 'has-error' : '' }}">
                                                <label class="required" for="status">{{ trans('global.status') }}</label>
                                                <select class="form-control" name="status" id="status" required>
                                                    @foreach (App\Models\AddCoupon::STATUS_SELECT as $key => $label)
                                                        <option value="{{ $key }}" {{ old('status', '1') === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('status'))
                                                    <span class="help-block">{{ $errors->first('status') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="is_first_booking">First Booking Coupon</label>
                                                <div class="checkbox" style="margin-top: 10px;">
                                                    <label>
                                                        <input type="checkbox" name="is_first_booking" id="is_first_booking" value="1" {{ old('is_first_booking') ? 'checked' : '' }}>
                                                        Use this code as the auto-suggested first booking offer
                                                    </label>
                                                </div>
                                                <small class="text-danger">Enabling this replaces any existing first-booking coupon pointer.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button class="btn btn-danger" type="submit">{{ trans('global.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const couponCodeInput = document.getElementById('coupon_code');
            const generateButton = document.getElementById('generateCouponButton');

            function generateCouponCode() {
                const characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                let coupon = '';
                for (let i = 0; i < 8; i++) {
                    coupon += characters.charAt(Math.floor(Math.random() * characters.length));
                }
                return coupon;
            }

            generateButton.addEventListener('click', function() {
                couponCodeInput.value = generateCouponCode();
                couponCodeInput.dispatchEvent(new Event('input'));
            });

            couponCodeInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/\s+/g, '');
            });
        });
    </script>
@endsection
