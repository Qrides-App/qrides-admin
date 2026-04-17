@extends('layouts.admin')
@section('content')
<section class="content">
    <div class="row gap-2">
        <div class="col-md-3 settings_bar_gap">
            <div class="box box-info box_info">
                <div class="">
                    <h4 class="all_settings f-18 mt-1" style="margin-left:15px;">Fare Test</h4>
                    @include('admin.generalSettings.general-setting-links.links')
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Vehicle-wise Fare Simulator</h3>
                </div>
                <form method="get" action="{{ route('admin.fareTest') }}" class="form-horizontal">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Distance (km)</label>
                            <div class="col-sm-3">
                                <input type="number" step="0.1" min="0" class="form-control" name="distance" value="{{ old('distance', $inputs['distance']) }}" required>
                            </div>
                            <label class="col-sm-3 control-label">Duration (min)</label>
                            <div class="col-sm-3">
                                <input type="number" step="1" min="0" class="form-control" name="duration_minutes" value="{{ old('duration_minutes', $inputs['duration_minutes']) }}" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Manual Surge</label>
                            <div class="col-sm-3">
                                <input type="number" step="0.1" min="0.1" class="form-control" name="surge" value="{{ old('surge', $inputs['surge']) }}" required>
                            </div>
                            <label class="col-sm-3 control-label">Wallet Amount</label>
                            <div class="col-sm-3">
                                <input type="number" step="0.01" min="0" class="form-control" name="wallet_amount" value="{{ old('wallet_amount', $inputs['wallet_amount']) }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Pickup Distance (km)</label>
                            <div class="col-sm-3">
                                <input type="number" step="0.1" min="0" class="form-control" name="pickup_distance_km" value="{{ old('pickup_distance_km', $inputs['pickup_distance_km']) }}">
                            </div>
                            <label class="col-sm-3 control-label">Waiting (min)</label>
                            <div class="col-sm-3">
                                <input type="number" step="0.1" min="0" class="form-control" name="waiting_minutes" value="{{ old('waiting_minutes', $inputs['waiting_minutes']) }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Toll</label>
                            <div class="col-sm-3">
                                <input type="number" step="0.01" min="0" class="form-control" name="toll_charge" value="{{ old('toll_charge', $inputs['toll_charge']) }}">
                            </div>
                            <label class="col-sm-3 control-label">Parking</label>
                            <div class="col-sm-3">
                                <input type="number" step="0.01" min="0" class="form-control" name="parking_charge" value="{{ old('parking_charge', $inputs['parking_charge']) }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Airport Fee</label>
                            <div class="col-sm-3">
                                <input type="number" step="0.01" min="0" class="form-control" name="airport_fee" value="{{ old('airport_fee', $inputs['airport_fee']) }}">
                            </div>
                            <label class="col-sm-3 control-label">Apply Airport Fee</label>
                            <div class="col-sm-3">
                                <select name="apply_airport_fee" class="form-control">
                                    <option value="1" {{ (int) old('apply_airport_fee', $inputs['apply_airport_fee']) === 1 ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ (int) old('apply_airport_fee', $inputs['apply_airport_fee']) === 0 ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Weather Key</label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control" name="weather_condition" placeholder="rain, storm" value="{{ old('weather_condition', $inputs['weather_condition']) }}">
                            </div>
                            <label class="col-sm-3 control-label">Event Key</label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control" name="event_key" placeholder="concert_night" value="{{ old('event_key', $inputs['event_key']) }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Currency</label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control" name="currency_code" value="{{ old('currency_code', $inputs['currency_code']) }}">
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info btn-space">Run Test</button>
                        <a class="btn btn-default" href="{{ route('admin.fareTest') }}">Reset</a>
                    </div>
                </form>
            </div>

            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Comparison Across Vehicle Types</h3>
                </div>
                <div class="box-body table-responsive">
                    @if(empty($results))
                        <div class="alert alert-warning">
                            No vehicle types available for comparison. Add vehicle types from <strong>Platform Setup → Vehicle Type</strong>.
                        </div>
                    @endif
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vehicle Type</th>
                                <th>Status</th>
                                <th>Per KM</th>
                                <th>Base</th>
                                <th>Per Min</th>
                                <th>Surge</th>
                                <th>Ride Fare</th>
                                <th>Booking</th>
                                <th>Pickup</th>
                                <th>Waiting</th>
                                <th>Tax</th>
                                <th>Total</th>
                                <th>Engine</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $row)
                                <tr>
                                    <td>{{ $row['item_type_id'] }}</td>
                                    <td>{{ $row['item_type_name'] }}</td>
                                    <td>{{ $row['item_type_status'] }}</td>
                                    <td>{{ $row['per_km'] }}</td>
                                    <td>{{ $row['base_fare'] }}</td>
                                    <td>{{ $row['time_component'] }}</td>
                                    <td>{{ $row['surge'] }}</td>
                                    <td>{{ $row['ride_fare_after_surge'] }}</td>
                                    <td>{{ $row['booking_fee'] }}</td>
                                    <td>{{ $row['pickup_charge'] }}</td>
                                    <td>{{ $row['waiting_charge'] }}</td>
                                    <td>{{ $row['tax_amount'] }}</td>
                                    <td><strong>{{ $row['total_price'] }}</strong></td>
                                    <td>
                                        @if(!empty($row['error']))
                                            <span class="text-danger">{{ $row['error'] }}</span>
                                        @else
                                            <span class="text-success">OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center">No vehicle types found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
