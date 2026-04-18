@extends('layouts.admin')
@section('content')
@php
$i = 0;
$j = 0;
@endphp
<div class="content admin-screen">
    @php
        $statuses = [
            'accepted' => trans('booking.booking_accepted'),
            'ongoing' => trans('booking.booking_running'),
            'cancelled' => trans('booking.booking_cancelled'),
            'rejected' => trans('booking.booking_rejected'),
            'completed' => trans('booking.booking_completed'),
        ];
        $selectedStatus = request()->input('status');
        $statusTabs = [
            ['key' => null, 'label' => trans('booking.booking_all'), 'route' => 'admin.bookings.index', 'isTrash' => false],
            ['key' => 'accepted', 'label' => trans('booking.booking_accepted'), 'route' => 'admin.bookings.index', 'isTrash' => false],
            ['key' => 'ongoing', 'label' => trans('booking.booking_running'), 'route' => 'admin.bookings.index', 'isTrash' => false],
            ['key' => 'completed', 'label' => trans('booking.booking_completed'), 'route' => 'admin.bookings.index', 'isTrash' => false],
            ['key' => 'cancelled', 'label' => trans('booking.booking_cancelled'), 'route' => 'admin.bookings.index', 'isTrash' => false],
            ['key' => 'rejected', 'label' => trans('booking.booking_rejected'), 'route' => 'admin.bookings.index', 'isTrash' => false],
            ['key' => null, 'label' => trans('booking.booking_trash'), 'route' => 'admin.bookings.trash', 'isTrash' => true],
        ];
        $currentQuery = request()->query();
    @endphp

    <div class="admin-screen-header">
        <div>
            <span class="admin-screen-header__eyebrow">Ride operations</span>
            <h1 class="admin-screen-header__title">Booking command center</h1>
            <p class="admin-screen-header__subtitle">Follow reservation flow, rider-driver assignment, route details, and payment state from one operational table.</p>
        </div>
        <div class="admin-screen-header__actions">
            <div class="admin-screen-header__meta">
                <span>Total bookings</span>
                <strong>{{ $bookings->total() }}</strong>
            </div>
        </div>
    </div>

    <div class="admin-surface admin-filter-shell">
        <div class="admin-filter-shell__header">
            <div>
                <h3>Filter booking activity</h3>
                <p>Search by date window, rider, driver, and ride status to surface operational issues quickly.</p>
            </div>
            <div class="admin-inline-stat">
                <span>Current page</span>
                <strong>{{ $bookings->currentPage() }}</strong>
            </div>
        </div>
        <form class="form-horizontal admin-filter-grid" enctype="multipart/form-data" action="" method="GET"
            accept-charset="UTF-8" id="bookingFilterForm">
            <div class="col-md-12 d-none">
                <input class="form-control" type="hidden" id="startDate" name="from" value="">
                <input class="form-control" type="hidden" id="endDate" name="to" value="">
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-12 col-xs-12">
                    <label>{{ trans('booking.date_range') }}</label>
                    <div class="input-group col-xs-12">
                        <input type="text" class="form-control" autocomplete="off" id="daterange-btn">
                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    </div>
                </div>

                <div class="col-md-3 col-sm-12 col-xs-12">
                    <label>{{ trans('booking.rider') }}</label>
                    <select class="form-control select2" name="customer" id="rider">
                        <option value="">{{ $searchCustomer }}</option>
                    </select>
                </div>

                <div class="col-md-3 col-sm-12 col-xs-12">
                    <label>{{ trans('booking.host') }}</label>
                    <select class="form-control select2" name="host" id="host">
                        <option value="">{{ $searchfield }}</option>
                    </select>
                </div>

                <div class="col-md-3 col-sm-12 col-xs-12">
                    <label>{{ trans('booking.booking_status') }}</label>
                    <select class="form-control" name="status" id="status">
                        <option value="">{{ trans('booking.select_status') }}</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="admin-filter-actions">
                        <button type="submit" name="btn" class="btn btn-primary btn-flat">
                            {{ trans('booking.filter') }}
                        </button>
                        <button type="button" name="reset_btn" id="resetBtn" class="btn btn-default btn-flat">
                            {{ trans('booking.reset') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="admin-chip-row">
                @foreach ($statusTabs as $status)
                    @php
                        $isActive = false;
                        if ($status['isTrash']) {
                            $isActive = request()->routeIs($status['route']);
                        } else {
                            if (is_null($status['key'])) {
                                $isActive = request()->routeIs($status['route']) && !request()->query('status');
                            } else {
                                $isActive = request()->query('status') === $status['key'];
                            }
                        }
                        $class = $isActive ? 'btn btn-primary' : 'btn btn-inactive';
                        $routeParams = $currentQuery;
                        if ($status['isTrash']) {
                            $url = route($status['route']);
                        } else {
                            if (is_null($status['key'])) {
                                unset($routeParams['status']);
                            } else {
                                $routeParams['status'] = $status['key'];
                            }
                            $url = route($status['route'], $routeParams);
                        }

                        $countKey = $status['key'] ?? ($status['isTrash'] ? 'trash' : 'all');
                        $count = $statusCounts[$countKey] ?? 0;
                    @endphp

                    <a class="{{ $class }}" href="{{ $url }}">
                        {{ $status['label'] }}
                        <span class="badge badge-pill badge-primary">{{ $count > 0 ? $count : 0 }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="panel panel-default admin-data-card">
        <div class="panel-heading">
            <div class="admin-panel-header">
                <div>
                    <h3>{{ trans('booking.all_bookings') }}</h3>
                    <p>Inspect booking lifecycle, route details, and payment coverage from the same operational grid.</p>
                </div>
                <div class="admin-inline-stat">
                    <span>Visible rows</span>
                    <strong>{{ $bookings->count() }}</strong>
                </div>
            </div>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-Booking">
                            <thead>
                                <tr>
                                    <th width="10"></th>
                                    <th>
                                        {{ trans('booking.reservation_code') }}
                                    </th>
                                    <th>
                                        {{ trans('booking.driver') }}
                                    </th>
                                    <th>
                                        {{ trans('booking.rider') }}
                                    </th>
                                    <th>
                                        {{ trans('booking.vehicle_information') }}
                                    </th>
                                    <th>
                                        {{ trans('booking.pickup_location') }}
                                    </th>
                                    <th>
                                        {{ trans('booking.destination') }}
                                    </th>
                                    <th>
                                        {{ trans('booking.ride_fare') }}
                                    </th>
                                    <th>
                                        {{ trans('booking.booking_date') }}
                                    </th>
                                    <th>
                                        {{ trans('booking.booking_status') }}
                                    </th>
                                    <th>
                                        {{ trans('booking.payment_method') }} / {{ trans('booking.payment_status') }}
                                    </th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bookings as $key => $booking)
                                <tr data-entry-id="{{ $booking->id }}">
                                    <td>

                                    </td>
                                    <td>
                                        <a target="_blank" class="badge badge-pill badge-primary live-badge"
                                            href="{{ route('admin.bookings.show', $booking->id) }}">
                                            <i class="fas fa-database table-icon"></i>
                                            {{ $booking->token }}
                                        </a>
                                        <span class="badge badge-pill badge-success live-badge">
                                            <i class="fas fa-fire table-icon"></i>
                                            {{ $booking->extension->ride_id }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($booking->host)
                                            <div class="admin-persona">
                                                <div class="admin-persona__avatar">
                                                    <a target="_blank"
                                                        href="{{ route('admin.driver.profile', ['driver_id' => $booking->host->id]) }}">
                                                        @if ($booking->host->profile_image)
                                                            <img src="{{ $booking->host->profile_image->getUrl('thumb') }}"
                                                                alt="Profile Image" class="img-circle">
                                                        @else
                                                            <img src="{{ asset('images/icon/userdefault.jpg') }}" class="img-circle"
                                                                alt="Default Image" style="display: inline-block;">
                                                        @endif
                                                    </a>
                                                </div>
                                                <div class="admin-persona__meta">
                                                    <strong><a target="_blank" href="{{ route('admin.driver.profile', ['driver_id' => $booking->host->id]) }}">{{ $booking->host->first_name }} {{ $booking->host->last_name }}</a></strong>
                                                    <span>Assigned driver</span>
                                                </div>
                                            </div>
                                        @else
                                            <span>--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($booking->user)
                                            <div class="admin-persona">
                                                <div class="admin-persona__avatar">
                                                    <a target="_blank"
                                                        href="{{route('admin.app-users.show', $booking->user->id)   }}">
                                                        @if ($booking->user->profile_image)
                                                            <img src="{{ $booking->user->profile_image->getUrl('thumb') }}"
                                                                class="img-circle">
                                                        @else
                                                            <img src="{{ asset('images/icon/userdefault.jpg') }}" alt="Default Image"
                                                                class="img-circle" style="display: inline-block;">
                                                        @endif
                                                    </a>
                                                </div>
                                                <div class="admin-persona__meta">
                                                    <strong><a target="_blank" href="{{route('admin.app-users.show', $booking->user->id)   }}">{{ $booking->user->first_name ?? '' }} {{ $booking->user->last_name ?? '' }}</a></strong>
                                                    <span>Rider profile</span>
                                                </div>
                                            </div>
                                        @else
                                            <span>--</span>
                                        @endif
                                    </td>
                                    <td class="car-type-animated">
                                        <div class="admin-list-stack">
                                            <strong>{{ $booking->item->item_Type->name ?? '-' }}</strong>
                                            <span>Make: {{ $booking->item->vehicleMake->name ?? '-' }}</span>
                                            <span>Model: {{ $booking->item->model ?? '-' }}</span>
                                            <span>Number: {{ strtoupper(optional($booking->item)->registration_number ?? '-') }}</span>
                                        </div>
                                    </td>
                                    <td class="pickup-address">
                                        <div class="admin-list-stack">
                                            <strong><i class="fas fa-map-marker-alt"></i> Pickup</strong>
                                            <span>{{ $booking->extension && is_array($booking->extension->pickup_location) ? $booking->extension->pickup_location['address'] : '' }}</span>
                                        </div>
                                    </td>

                                    <td class="dropoff-address">
                                        <div class="admin-list-stack">
                                            <strong><i class="fas fa-map-pin"></i> Destination</strong>
                                            <span>{{ $booking->extension && is_array($booking->extension->dropoff_location) ? $booking->extension->dropoff_location['address'] : '' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="admin-list-stack">
                                            <strong>{{ ($booking->total ?? '') . ' ' . ($general_default_currency->meta_value ?? '') }}</strong>
                                            <span>Ride fare</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="admin-list-stack">
                                            <strong>{{ $booking->created_at ? $booking->created_at->format('Y-m-d') : '' }}</strong>
                                            <span>{{ $booking->created_at ? $booking->created_at->format('h:i A') : '' }}</span>
                                        </div>
                                    </td>
                                    @php
                                    $statusClasses = [
                                    'ongoing' => [
                                    'class' => 'badge badge-pill label-secondary live-badge',
                                    'label' =>
                                    '<span class="live-dot"></span> Live'
                                    ],
                                    'cancelled' => ['class' => 'badge badge-pill label-danger', 'label' => 'Cancelled'],
                                    'accepted' => ['class' => 'badge badge-pill label-success', 'label' => 'Accepted'],
                                    'approved' => ['class' => 'badge badge-pill label-success', 'label' => 'Approved'],
                                    'declined' => ['class' => 'badge badge-pill label-warning', 'label' => 'Declined'],
                                    'rejected' => ['class' => 'badge badge-pill label-warning', 'label' => 'Rejected'],
                                    'completed' => ['class' => 'badge badge-pill label-info', 'label' => 'Completed'],
                                    'refunded' => ['class' => 'badge badge-pill label-primary', 'label' => 'Refunded'],
                                    'confirmed' => [
                                    'class' => 'badge badge-pill label-success',
                                    'label' =>
                                    'Confirmed'
                                    ],
                                    'pending' => ['class' => 'badge badge-pill label-warning', 'label' => 'Pending'],
                                    ];
                                    $normalizedStatus = strtolower($booking->status ?? '');
                                    @endphp

                                    <td>
                                        @if(array_key_exists($normalizedStatus, $statusClasses))
                                        <span class="{!! $statusClasses[$normalizedStatus]['class'] !!}">
                                            {!! $statusClasses[$normalizedStatus]['label'] !!}
                                        </span>
                                        @else
                                        {{ $booking->status }}
                                        @endif
                                    </td>

                                    <td>
                                        @php
                                        $paymentMethod = strtolower($booking->payment_method) ?? '';
                                        $badgeClass = match (strtolower($paymentMethod)) {
                                        'cash' => 'badge badge-pill label-secondary',
                                        'card',
                                        'credit card',
                                        'debit card'
                                        => 'badge badge-pill label-primary',
                                        'paypal' => 'badge badge-pill badge-info',
                                        'stripe' => 'badge badge-pill label-warning',
                                        'wallet' => 'badge badge-pill label-success',
                                        default => 'badge badge-pill label-light',
                                        };
                                        @endphp

                                        <div class="{{ $badgeClass }}">{{ ucfirst($paymentMethod) }}</div>
                                        @if ($booking->payment_status === 'paid')
                                        <span class="badge badge-pill label-success">paid</span>
                                        @elseif ($booking->payment_status === 'notpaid')
                                        <span class="badge badge-pill label-danger">notpaid</span>
                                        @endif

                                    </td>
                                    <td>
                                        @php
                                        $isTrashPage = request()->is('admin/bookings/trash');
                                        @endphp
                                        <div class="admin-actions">
                                        <!-- Restore Form -->
                                        @if($isTrashPage)
                                        <form id="restore-form-{{ $booking->id }}"
                                            action="{{ route('admin.bookings.restore', $booking->id) }}" method="POST"
                                            style="display: inline-block;">
                                            @csrf
                                            <button type="button" class="btn btn-xs btn-success restore-btn"
                                                data-id="{{ $booking->id }}">
                                                <i class="fa fa-undo" aria-hidden="true"></i>
                                            </button>
                                        </form>

                                        <!-- Permanent Delete Form -->
                                        <form id="delete-form-{{ $booking->id }}"
                                            action="{{ route('admin.bookings.permanentDelete', $booking->id) }}"
                                            method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="button" class="btn btn-xs btn-danger permanent-delete"
                                                data-id="{{ $booking->id }}">
                                                <i class="fa fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        @else
                                        @can('booking_delete')
                                        <button type="button" class="btn btn-xs btn-danger delete-booking-button"
                                            data-id="{{ $booking->id }}">
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </button>
                                        @endcan

                                        <!-- View Button (always shown) -->
                                        <a target="_blank" class="badge badge-pill badge-primary live-badge"
                                            href="{{ route('admin.bookings.show', $booking->id) }}">
                                            <i class="fa fa-eye" aria-hidden="true"></i>
                                        </a>
                                        @endif
                                        </div>
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>
                </table>
                <nav aria-label="...">
                    <ul class="pagination justify-content-end">
                        @if ($bookings->currentPage() > 1)
                        <li class="page-item">
                            <a class="page-link" href="{{ $bookings->previousPageUrl() }}" tabindex="-1">{{
                                trans('global.previous') }}</a>
                        </li>
                        @else
                        <li class="page-item disabled">
                            <span class="page-link">{{ trans('global.previous') }}</span>
                        </li>
                        @endif
                        @for ($i = 1; $i <= $bookings->lastPage(); $i++)
                            <li class="page-item {{ $i == $bookings->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $bookings->url($i) }}">{{ $i }}</a>
                            </li>
                            @endfor
                            @if ($bookings->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $bookings->nextPageUrl() }}">{{
                                    trans('global.next') }}</a>
                            </li>
                            @else
                            <li class="page-item disabled">
                                <span class="page-link">{{ trans('global.next') }}</span>
                            </li>
                            @endif
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
@endsection
@include('admin.common.addSteps.footer.footerJs')
@section('scripts')
@parent

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.delete-booking-button');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const bookingId = this.getAttribute('data-id');
                Swal.fire({
                    title: '{{ trans('global.are_you_sure') }}',
                    text: '{{ trans('global.delete_confirmation') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            willOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        deleteBooking(bookingId);
                    }
                });
            });
        });

        function deleteBooking(bookingId) {
            const url = `{{ route('admin.bookings.destroy', ':id') }}`.replace(':id', bookingId);
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                success: function (response) {
                    Swal.close();
                    toastr.success('{{ trans('global.delete_success') }}', 'Success', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    location.reload();
                },
                error: function (xhr, status, error) {
                    Swal.close();
                    toastr.error('{{ trans('global.deletion_error') }}', 'Error', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    console.error(error);
                }
            });
        }
    });

    $(function () {
        let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons);

        function handleDeletion(route) {
            return function (e, dt, node, config) {
                var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
                    return $(entry).data('entry-id');
                });

                if (ids.length === 0) {
                    Swal.fire({
                        title: '{{ trans('global.no_entries_selected') }}',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                Swal.fire({
                    title: '{{ trans('global.are_you_sure') }}',
                    text: '{{ trans('global.delete_confirmation') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var csrfToken = $('meta[name="csrf-token"]').attr('content');
                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            method: 'POST',
                            url: route,
                            data: {
                                ids: ids,
                                _method: 'DELETE'
                            }
                        }).done(function (response) {
                            location.reload();
                        }).fail(function (xhr, status, error) {
                            Swal.fire(
                                '{{ trans('global.error') }}',
                                '{{ trans('global.delete_error') }}',
                                'error'
                            );
                        });
                    }
                });
            };
        }

        @php
        $deleteRoute = request() -> is('admin/bookings/trash')
            ? route('admin.bookings.deleteTrashAll')
            : route('admin.bookings.deleteAll');
        @endphp

        let deleteRoute = "{{ $deleteRoute }}";

        let deleteButton = {
            text: '{{ trans('global.delete_all') }}',
            className: 'btn-danger',
            action: handleDeletion(deleteRoute)
        };

        dtButtons.push(deleteButton);

        let table = $('.datatable-Booking:not(.ajaxTable)').DataTable({
            buttons: dtButtons
        });
        $('a[data-toggle="tab"]').on('shown.bs.tab click', function (e) {
        });
    });
</script>

<script>
    $(document).ready(function () {
        $('#host').select2({
            ajax: {
                url: "{{ route('admin.searchHost') }}",
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                id: item.id,
                                text: item.first_name,
                            };
                        })
                    };
                },
                cache: true,
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("Error while fetching customer data:", textStatus, errorThrown);
                }
            }
        });
        var selectedUserId = "{{ $searchfieldId }}";
        var selectedUserName = "{{ $searchfield }}";

        if (selectedUserId) {
            var option = new Option(selectedUserName, selectedUserId, true, true);
            $('#host').append(option).trigger('change');
        }
    });
    $(document).ready(function () {
        $('#rider').select2({
            ajax: {
                url: "{{ route('admin.searchUser') }}",
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                id: item.id,
                                text: item.first_name,
                            };
                        })
                    };
                },
                cache: true,
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("Error while fetching customer data:", textStatus, errorThrown);
                }
            }
        });
        var selectedUserId = "{{ $searchCustomerId }}";
        var selectedUserName = "{{ $searchCustomer }}";

        if (selectedUserId) {
            var option = new Option(selectedUserName, selectedUserId, true, true);
            $('#rider').append(option).trigger('change');
        }

        $(document).ready(function () {
            $('.restore-btn').on('click', function () {
                var bookingId = $(this).data('id');
                var form = $('#restore-form-' + bookingId);

                Swal.fire({
                    title: "Restore Booking",
                    text: "Are you sure you want to restore this item?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, restore it!",
                    cancelButtonText: "Cancel",
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Restoring',
                            text: 'Please wait while restoring...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            willOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Perform AJAX request to restore the booking
                        $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: form.serialize(),
                            success: function (response) {
                                Swal.close();
                                toastr.success('Booking restored successfully!',
                                    'Success', {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });
                                // Optionally, update UI as needed (e.g., remove the restored item from the list)
                                location.reload(); // Example: Reload the page
                            },
                            error: function (response) {
                                Swal.close();
                                toastr.error(
                                    'An error occurred while restoring the booking.',
                                    'Error', {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });
                            }
                        });
                    }
                });
            });

            $('.permanent-delete').on('click', function () {
                var bookingId = $(this).data('id');
                var form = $('#delete-form-' + bookingId);

                Swal.fire({
                    title: "Delete Booking Permanently",
                    text: "Are you sure you want to permanently delete this item?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "Cancel",
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting',
                            text: 'Please wait while deleting...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            willOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Perform AJAX request to permanently delete the booking
                        $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: form.serialize(),
                            success: function (response) {
                                Swal.close();
                                toastr.success('Booking permanently deleted!',
                                    'Success', {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });
                                // Optionally, update UI as needed (e.g., remove the deleted item from the list)
                                location.reload(); // Example: Reload the page
                            },
                            error: function (response) {
                                Swal.close();
                                toastr.error(
                                    'An error occurred while deleting the booking.',
                                    'Error', {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });
                            }
                        });
                    }
                });
            });
        });
    });
    $(document).ready(function () {
        $('.restore-btn').on('click', function () {
            var bookingId = $(this).data('id');
            var form = $('#restore-form-' + bookingId);

            Swal.fire({
                title: "Restore Booking",
                text: "Are you sure you want to restore this item?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, restore it!",
                cancelButtonText: "Cancel",
            }).then(function (result) {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Restoring',
                        text: 'Please wait while restoring...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Perform AJAX request to restore the booking
                    $.ajax({
                        url: form.attr('action'),
                        method: 'POST',
                        data: form.serialize(),
                        success: function (response) {
                            Swal.close();
                            toastr.success('Booking restored successfully!',
                                'Success', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                            // Optionally, update UI as needed (e.g., remove the restored item from the list)
                            location.reload(); // Example: Reload the page
                        },
                        error: function (response) {
                            Swal.close();
                            toastr.error(
                                'An error occurred while restoring the booking.',
                                'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    });
                }
            });
        });

        $('.permanent-delete').on('click', function () {
            var bookingId = $(this).data('id');
            var form = $('#delete-form-' + bookingId);

            Swal.fire({
                title: "Delete Booking Permanently",
                text: "Are you sure you want to permanently delete this item?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel",
            }).then(function (result) {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting',
                        text: 'Please wait while deleting...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Perform AJAX request to permanently delete the booking
                    $.ajax({
                        url: form.attr('action'),
                        method: 'POST',
                        data: form.serialize(),
                        success: function (response) {
                            Swal.close();
                            toastr.success('Booking permanently deleted!',
                                'Success', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                            // Optionally, update UI as needed (e.g., remove the deleted item from the list)
                            location.reload(); // Example: Reload the page
                        },
                        error: function (response) {
                            Swal.close();
                            toastr.error(
                                'An error occurred while deleting the booking.',
                                'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
