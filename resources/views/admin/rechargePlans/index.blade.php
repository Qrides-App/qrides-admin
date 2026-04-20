@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Recharge Settings</h3>
                    </div>
                    <form method="POST" action="{{ route('admin.recharge-plans.settings') }}">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label for="driver_recharge_amount_per_day">Amount Per Day</label>
                                <input type="number" step="0.01" min="1" class="form-control"
                                    id="driver_recharge_amount_per_day" name="driver_recharge_amount_per_day"
                                    value="{{ old('driver_recharge_amount_per_day', $amountPerDay) }}" required>
                            </div>
                            <div class="form-group">
                                <label for="driver_recharge_currency">Currency Code</label>
                                <input type="text" class="form-control" id="driver_recharge_currency"
                                    name="driver_recharge_currency"
                                    value="{{ old('driver_recharge_currency', $currencyCode) }}" maxlength="10" required>
                            </div>
                            <div class="form-group">
                                <label for="driver_recharge_gst_percentage">GST (%)</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="driver_recharge_gst_percentage" name="driver_recharge_gst_percentage"
                                    value="{{ old('driver_recharge_gst_percentage', $gstPercentage) }}">
                                <small class="text-muted">Applied on top of recharge plan base amount. Saving settings now also syncs the visible Daily Plan row below.</small>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Add Recharge Plan</h3>
                    </div>
                    <form method="POST" action="{{ route('admin.recharge-plans.store') }}">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" class="form-control" name="name" placeholder="Daily / Weekly / Monthly"
                                    required>
                            </div>
                            <div class="form-group">
                                <label>Duration (Days)</label>
                                <input type="number" min="1" max="365" class="form-control" name="duration_days" required>
                            </div>
                            <div class="form-group">
                                <label>Amount</label>
                                <input type="number" step="0.01" min="1" class="form-control" name="amount" required>
                                <small class="text-muted">Base amount before GST.</small>
                            </div>
                            <div class="form-group">
                                <label>Currency</label>
                                <input type="text" class="form-control" name="currency_code"
                                    value="{{ $currencyCode }}" maxlength="10" required>
                            </div>
                            <div class="form-group">
                                <label>Sort Order</label>
                                <input type="number" min="0" class="form-control" name="sort_order" value="0">
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" checked> Active
                                </label>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-success">Add Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Recharge Plans</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Days</th>
                                    <th>Amount</th>
                                    <th>GST</th>
                                    <th>Total</th>
                                    <th>Currency</th>
                                    <th>Active</th>
                                    <th>Sort</th>
                                    <th style="width: 320px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    @php
                                        $gstAmount = round(((float) $plan->amount * (float) $gstPercentage) / 100, 2);
                                        $totalAmount = round((float) $plan->amount + $gstAmount, 2);
                                    @endphp
                                    <tr>
                                        <td>{{ $plan->id }}</td>
                                        <td>{{ $plan->name }}</td>
                                        <td>{{ $plan->duration_days }}</td>
                                        <td>{{ number_format((float) $plan->amount, 2) }}</td>
                                        <td>{{ number_format((float) $gstPercentage, 2) }}%</td>
                                        <td>{{ number_format($totalAmount, 2) }}</td>
                                        <td>{{ $plan->currency_code }}</td>
                                        <td>{{ $plan->is_active ? 'Yes' : 'No' }}</td>
                                        <td>{{ $plan->sort_order }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.recharge-plans.update', $plan->id) }}"
                                                style="display:inline-block; width: 235px;">
                                                @csrf
                                                @method('PUT')
                                                <div class="row" style="margin:0;">
                                                    <div class="col-xs-4" style="padding:0 4px;">
                                                        <input class="form-control input-sm" type="number" name="duration_days"
                                                            min="1" value="{{ $plan->duration_days }}" required>
                                                    </div>
                                                    <div class="col-xs-4" style="padding:0 4px;">
                                                        <input class="form-control input-sm" type="number" step="0.01" min="1"
                                                            name="amount" value="{{ $plan->amount }}" required>
                                                    </div>
                                                    <div class="col-xs-4" style="padding:0 4px;">
                                                        <input class="form-control input-sm" type="number" min="0"
                                                            name="sort_order" value="{{ $plan->sort_order }}">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="name" value="{{ $plan->name }}">
                                                <input type="hidden" name="currency_code" value="{{ $plan->currency_code }}">
                                                <input type="hidden" name="is_active" value="{{ $plan->is_active ? 1 : 0 }}">
                                                <button type="submit" class="btn btn-xs btn-primary" style="margin-top:6px;">Update</button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.recharge-plans.destroy', $plan->id) }}"
                                                style="display:inline-block;"
                                                onsubmit="return confirm('Delete this recharge plan?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No recharge plans found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
