@extends('layouts.admin')
@section('content')
<section class="content">
    <div class="row gap-2">
        <div class="col-md-3 settings_bar_gap">
            <div class="box box-info box_info">
                <div class="">
                    <h4 class="all_settings f-18 mt-1" style="margin-left:15px;">Fare Settings</h4>
                    @include('admin.generalSettings.general-setting-links.links')
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Distance + Time + Surge + Platform Fees</h3>
                </div>
                <form method="post" action="{{ route('admin.fareSettingUpdate') }}" class="form-horizontal">
                    {{ csrf_field() }}
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Base Fare</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.01" min="0" name="fare_base" class="form-control" value="{{ old('fare_base', $fare_base) }}" required>
                                <small class="text-muted">Covers first included km.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Included KM</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.1" min="0" name="fare_included_km" class="form-control" value="{{ old('fare_included_km', $fare_included_km) }}" required>
                                <small class="text-muted">Distance covered by base fare.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Per Minute</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.01" min="0" name="fare_per_min" class="form-control" value="{{ old('fare_per_min', $fare_per_min) }}" required>
                                <small class="text-muted">Time component (waiting/traffic).</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Minimum Fare</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.01" min="0" name="fare_min_fare" class="form-control" value="{{ old('fare_min_fare', $fare_min_fare) }}" required>
                                <small class="text-muted">Floor after surge/discounts.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Surge Cap</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.1" min="0.1" name="fare_surge_cap" class="form-control" value="{{ old('fare_surge_cap', $fare_surge_cap) }}" required>
                                <small class="text-muted">Max multiplier (e.g., 2.0).</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Surge Floor</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.1" min="0.1" name="fare_surge_floor" class="form-control" value="{{ old('fare_surge_floor', $fare_surge_floor) }}" required>
                                <small class="text-muted">Min multiplier (e.g., 1.0 normal, 0.8 off-peak).</small>
                            </div>
                        </div>

                        <hr>
                        <h4 class="col-sm-12">Fees & Taxes</h4>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Booking Fee</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.01" min="0" name="fare_booking_fee" class="form-control" value="{{ old('fare_booking_fee', $fare_booking_fee) }}" required>
                                <small class="text-muted">Flat platform fee per trip.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Pickup Included KM</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.1" min="0" name="fare_pickup_included_km" class="form-control" value="{{ old('fare_pickup_included_km', $fare_pickup_included_km) }}" required>
                                <small class="text-muted">Free pickup distance before pickup charge starts.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Pickup Per KM</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.01" min="0" name="fare_pickup_per_km" class="form-control" value="{{ old('fare_pickup_per_km', $fare_pickup_per_km) }}" required>
                                <small class="text-muted">Rate applied to billable pickup kms.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Waiting Per Min</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.01" min="0" name="fare_waiting_per_min" class="form-control" value="{{ old('fare_waiting_per_min', $fare_waiting_per_min) }}" required>
                                <small class="text-muted">Waiting charge per minute.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Tax Percent</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.01" min="0" max="100" name="fare_tax_percent" class="form-control" value="{{ old('fare_tax_percent', $fare_tax_percent) }}" required>
                                <small class="text-muted">Applied after coupon on subtotal.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Airport Fee</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.01" min="0" name="fare_airport_fee" class="form-control" value="{{ old('fare_airport_fee', $fare_airport_fee) }}" required>
                                <small class="text-muted">Default airport surcharge when enabled.</small>
                            </div>
                        </div>

                        <hr>
                        <h4 class="col-sm-12">Time-Based Surge Rules</h4>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rules JSON</label>
                            <div class="col-sm-6">
                                <textarea name="fare_time_surge_rules" rows="6" class="form-control" placeholder='[{"name":"Weekday Morning","days":[1,2,3,4,5],"start":"08:00","end":"11:00","multiplier":1.4},{"name":"Weekend Night","days":[6,0],"start":"18:00","end":"23:30","multiplier":1.6}]'>{{ old('fare_time_surge_rules', $fare_time_surge_rules) }}</textarea>
                                <small class="text-muted">
                                    Optional. JSON array of rules. Fields: name, days (0=Sun), start/end (HH:MM, 24h), multiplier (>0).
                                    Applied with traffic surge and clamped between floor/cap.
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info btn-space">Save</button>
                        <a class="btn btn-danger" href="{{ route('admin.settings') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
