@extends('layouts.admin')
@section('content')
<div class="content">
    @php
        $currency = Config::get('general.general_default_currency');
    @endphp

    <div class="admin-page-header">
        <div>
            <h3 class="admin-page-title">{{ trans('payout.payout_title_singular') }} {{ trans('payout.list') }}</h3>
            <p class="admin-page-subtitle">Track requests, approve releases and monitor payout health.</p>
        </div>
    </div>

    @can('payout_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.payouts.create') }}">
                    {{ trans('global.add') }} {{ trans('global.payout_title_singular') }}
                </a>
            </div>
        </div>
    @endcan

    <div class="box booking-filter-card">
        <div class="box-body">
            <form class="form-horizontal" enctype="multipart/form-data" action="" method="GET" accept-charset="UTF-8" id="filterForm">
                <div class="col-md-12 d-none">
                    <input class="form-control" type="hidden" id="startDate" name="from" value="">
                    <input class="form-control" type="hidden" id="endDate" name="to" value="">
                </div>

                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-3 col-sm-12 col-xs-12">
                            <label>{{ trans('global.date_range') }}</label>
                            <div class="input-group col-xs-12">
                                <input type="text" class="form-control" id="daterange-btn">
                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-12 col-xs-12">
                            <label>{{ trans('global.status') }}</label>
                            <select class="form-control" name="status" id="status">
                                <option value="">{{ trans('global.all') }}</option>
                                @foreach(['Success' => 'Success', 'Pending' => 'Requested', 'Rejected' => 'Rejected'] as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 col-sm-12 col-xs-12">
                            <label>{{ trans('global.vendor_name') }}</label>
                            <select class="form-control select2" name="vendor" data-vendor-id="{{ $vendorId }}" data-vendor-name="{{ $vendorName }}" id="payoutDriver">
                                <option value="">{{ $vendorName }}</option>
                            </select>
                        </div>

                        <div class="col-md-2 col-sm-12 col-xs-12 booking-filter-actions">
                            <button type="submit" name="btn" class="btn btn-primary btn-flat">{{ trans('global.filter') }}</button>
                            <button type="button" id="resetBtn" class="btn btn-default btn-flat">{{ trans('global.reset') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row booking-summary-grid">
        <div class="col-md-3 col-sm-6">
            <div class="booking-summary-card">
                <div class="booking-summary-label">{{ trans('payout.total_payouts') }}</div>
                <div class="booking-summary-value">{{ $summary['total_payouts'] }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="booking-summary-card">
                <div class="booking-summary-label">{{ trans('payout.total_amount') }}</div>
                <div class="booking-summary-value">{{ formatCurrency($summary['total_amount']) . ' ' . $currency }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="booking-summary-card">
                <div class="booking-summary-label">{{ trans('payout.pending_amount') }}</div>
                <div class="booking-summary-value">{{ formatCurrency($summary['pending_amount']) . ' ' . $currency }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="booking-summary-card">
                <div class="booking-summary-label">{{ trans('payout.success_amount') }}</div>
                <div class="booking-summary-value">{{ formatCurrency($summary['success_amount']) . ' ' . $currency }}</div>
            </div>
        </div>
    </div>

    <div class="row booking-status-tabs">
        <div class="col-lg-12">
            @php $statuses = ['' => 'all', 'Success' => 'Success', 'Pending' => 'Requested', 'Rejected' => 'Rejected']; @endphp
            @foreach($statuses as $value => $label)
                <a class="btn {{ request('status') === $value || ($value === '' && !request()->has('status')) ? 'btn-primary booking-status-chip is-active' : 'btn-default booking-status-chip' }}"
                    href="{{ route('admin.payouts.index', array_merge(request()->except('btn', 'page'), ['status' => $value ?: null])) }}">
                    {{ trans('payout.' . strtolower($label)) }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default booking-table-panel">
                <div class="panel-heading">
                    {{ trans('payout.payout_title_singular') }} {{ trans('payout.list') }}
                </div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped table-hover ajaxTable datatable datatable-Payout table-responsive">
                        <thead>
                            <tr>
                                <th>{{ trans('payout.id') }}</th>
                                <th>{{ trans('payout.vendor_name') }}</th>
                                <th>{{ trans('payout.amount') }}</th>
                                <th>{{ trans('payout.payment_method') }}</th>
                                <th>{{ trans('payout.payout_status') }}</th>
                                <th>{{ trans('payout.request_status') }}</th>
                                <th>{{ trans('payout.proof') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payouts as $payout)
                                <tr data-entry-id="{{ $payout->id }}">
                                    <td>{{ $payout->id }}</td>
                                    <td>
                                        @if($payout->vendor)
                                            <a href="{{ route('admin.driver.profile', $payout->vendor->id) }}" target="_blank">
                                                {{ $payout->vendor->first_name }} {{ $payout->vendor->last_name }}
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{ formatCurrency($payout->amount) }} {{ $currency }}</td>
                                    <td>
                                        @if (!empty($payout->payment_method))
                                            <span class="badge badge-info">{{ $payout->payment_method }}</span>
                                        @else
                                            <span class="badge badge-warning">{{ trans('payout.manual_payment') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $status = $payout->payout_status;
                                            $badgeClass = match ($status) {
                                                'Pending' => 'label-danger',
                                                'Rejected' => 'label-rejected',
                                                'Success' => 'label-success',
                                                default => 'label-default',
                                            };

                                            $icon = match ($status) {
                                                'Pending' => 'fa-clock',
                                                'Rejected' => 'fa-times-circle',
                                                'Success' => 'fa-check-circle',
                                                default => 'fa-info-circle',
                                            };
                                        @endphp
                                        <span class="badge badge-pill {{ $badgeClass }}">
                                            <i class="fa {{ $icon }}"></i> {{ $status }}
                                        </span>
                                    </td>

                                    <td>
                                        @if($payout->payout_status === 'Pending')
                                            <div class="mb-1">
                                                <a class="badge badge-pill label-success open-payout-modal animate__animated animate__pulse animate__infinite animate__slow d-inline-block w-100"
                                                    href="#" data-payout-id="{{ $payout->id }}" data-amount="{{ $payout->amount }}"
                                                    data-vendor="{{ $payout->vendor->first_name }} {{ $payout->vendor->last_name }}">
                                                    <i class="fas fa-check"></i> {{ trans('payout.approve') }}
                                                </a>
                                                &nbsp; &nbsp;
                                                <a class="badge badge-pill label-rejected payout-reject animate__animated animate__pulse animate__infinite animate__slow d-inline-block w-100"
                                                    href="#" data-payout-id="{{ $payout->id }}" data-amount="{{ $payout->amount }}"
                                                    data-vendor="{{ $payout->vendor->first_name }} {{ $payout->vendor->last_name }}">
                                                    <i class="fas fa-times"></i> {{ trans('payout.reject') }}
                                                </a>
                                            </div>
                                        @elseif($payout->payout_status === 'Success')
                                            <span class="badge badge-pill label-success disabled-span d-inline-block w-100">
                                                <i class="fas fa-check-circle"></i> {{ trans('payout.success') }}
                                            </span>
                                        @elseif($payout->payout_status === 'Rejected')
                                            <span class="badge badge-pill label-rejected disabled-span d-inline-block w-100">
                                                <i class="fas fa-times-circle"></i> {{ trans('payout.rejected') }}
                                            </span>
                                        @else
                                            <span class="badge badge-pill label-default disabled-span d-inline-block w-100">
                                                <i class="fas fa-info-circle"></i> {{ trans('global.done') }}
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($payout->payout_proof)
                                            <a href="{{ $payout->payout_proof->url }}" target="_blank">
                                                <i class="fas fa-file-alt text-success"></i>
                                            </a>
                                        @else
                                            <i class="fas fa-times-circle text-danger" title="No Proof"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            @if($payouts->isEmpty())
                                <tr>
                                    <td colspan="7">
                                        <div class="table-empty-state">
                                            <h4>No payouts available for the selected filters</h4>
                                            <p>Try resetting date, vendor, or status filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    <nav aria-label="...">
                        <ul class="pagination justify-content-end">
                            @if ($payouts->currentPage() > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ $payouts->previousPageUrl() }}" tabindex="-1">{{ trans('payout.previous') }}</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link">{{ trans('payout.previous') }}</span>
                                </li>
                            @endif
                            @for ($i = 1; $i <= $payouts->lastPage(); $i++)
                                <li class="page-item {{ $i == $payouts->currentPage() ? 'active' : '' }}">
                                    <a class="page-link" href="{{ $payouts->url($i) }}">{{ $i }}</a>
                                </li>
                            @endfor
                            @if ($payouts->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link" href="{{ $payouts->nextPageUrl() }}">{{ trans('payout.next') }}</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link">{{ trans('payout.next') }}</span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payoutModal" tabindex="-1" role="dialog" aria-labelledby="payoutModalLabel">
    <div class="modal-dialog" role="document">
        <form id="payoutForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="payout_id" id="modalPayoutId">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="payoutModalLabel">Release Funds</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Vendor:</strong></label>
                        <p class="form-control-static" id="modalVendor"></p>
                    </div>

                    <div class="form-group">
                        <label><strong>Amount:</strong></label>
                        <p class="form-control-static" id="modalAmount"></p>
                    </div>

                    <div class="form-group">
                        <label for="payoutProof">Upload Payout Proof <span class="text-danger">*</span></label>
                        <input type="file" name="payout_proof" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="payoutNote">Notes</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Submit & Release</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
    @parent
    <script>
        var payoutVendorSearchUrl = "{{ route('admin.payoutVendorSearch') }}";
        var payoutUpdateStatus = "{{ route('admin.payouts.updateStatus', ':payoutId') }}";
    </script>
@endsection
