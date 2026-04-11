@extends('layouts.admin')
@section('content')
@php $i = 0; $j = 0; @endphp
<div class="content">
    <div class="box">
        <div class="box-body">
            <form class="form-horizontal" enctype="multipart/form-data" action="" method="GET" accept-charset="UTF-8"
                id="appusersFilterForm">
                @if(request()->has('host_status'))
                <input type="hidden" name="host_status" value="{{ request()->input('host_status') }}">
                @endif
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
                                <option value="">All</option>
                                <option value="1" {{ request()->input('status') == '1' ? 'selected' : '' }}>Active
                                </option>
                                <option value="0" {{ request()->input('status') == '0' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                        @php
                        $label = 'Rider';
                        @endphp
                        <div class="col-md-3 col-sm-12 col-xs-12">
                            <label>{{ $label }}</label>
                            <select class="form-control select2" name="customer" id="customer">
                                <option value="">{{ $searchfield }}</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-2 col-xs-4 mt-5 mt-4">
                            <button type="submit" name="btn" class="btn btn-primary btn-flat">{{ trans('global.filter')
                                }}</button>
                            <button type="button" id="resetBtn" class="btn btn-primary btn-flat ">{{
                                trans('global.reset') }}</button>
                        </div>

                    </div>
                </div>

            </form>
        </div>
    </div>
    <div style="margin-left: 5px; margin-bottom: 6px;" class="row">
        <div class="col-lg-12">
            {{-- Live --}}
            <a class="btn {{ request()->routeIs('admin.app-users.index') && is_null(request()->query('status')) && !request()->has('host_status') ? 'btn-primary' : 'btn-inactive' }}"
                href="{{ route('admin.app-users.index', array_merge(request()->except(['status', 'host_status']), ['status' => null])) }}">
                {{ trans('global.live') }}
                <span class="badge badge-pill badge-primary">{{ $statusCounts['live'] > 0 ? $statusCounts['live'] : 0
                    }}</span>
            </a>

            {{-- Active --}}
            <a class="btn {{ request()->query('status') === '1' && !request()->has('host_status') ? 'btn-primary' : 'btn-inactive' }}"
                href="{{ route('admin.app-users.index', array_merge(request()->except('host_status'), ['status' => 1])) }}">
                Active
                <span class="badge badge-pill badge-primary">{{ $statusCounts['active'] > 0 ? $statusCounts['active'] :
                    0 }}</span>
            </a>

            {{-- Inactive --}}
            <a class="btn {{ request()->query('status') === '0' && !request()->has('host_status') ? 'btn-primary' : 'btn-inactive' }}"
                href="{{ route('admin.app-users.index', array_merge(request()->except('host_status'), ['status' => 0])) }}">
                Inactive
                <span class="badge badge-pill badge-primary">{{ $statusCounts['inactive'] > 0 ?
                    $statusCounts['inactive'] : 0 }}</span>
            </a>



        </div>

    </div>



    <div id="loader" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>




    <div class="panel panel-default">
        <div class="panel-heading">
            {{ $label }} {{ trans('global.list') }}
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-AppUser">
                    <thead>
                        <tr>
                            <th></th>

                            <th>
                                {{ trans('global.id') }}
                            </th>
                            <th>
                                {{ trans('global.name') }}
                            </th>

                            <th>
                                {{ trans('global.email') }}
                            </th>
                            <th>
                                {{ trans('global.phone') }}
                            </th>

                            <th>
                                {{ trans('global.status') }}
                            </th>

                            <th>&nbsp;

                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appUsers as $key => $appUser)
                        <tr data-entry-id="{{$appUser->id}}">
                            <td></td>
                            <td>
                                <a target="_blank" class="btn btn-xs btn-primary"
                                    href="{{ route('admin.app-users.show', $appUser->id) }}"> #{{ $appUser->id ?? ''
                                    }}</a>
                            </td>
                            <td>
                                @if($appUser->profile_image)
                                <a href="{{ $appUser->profile_image->getUrl() }}" target="_blank"
                                    style="display: inline-block">
                                    <img src="{{ $appUser->profile_image->getUrl('thumb') }}">
                                </a>
                                @else
                                <img src="{{ asset('images/icon/userdefault.jpg') }}" alt="Default Image"
                                    style="display: inline-block;">
                                @endif
                                <a target="_blank" class="btn btn-xs btn-primary"
                                    href="{{ route('admin.app-users.show', $appUser->id) }}">
                                    {{ $appUser->first_name ?? '' }} {{ $appUser->last_name ?? '' }}
                                </a>

                            </td>
                            <td>

                                @can('app_user_contact_access')
                                {{ $appUser->email }}
                                @else
                                {{ maskEmail($appUser->email) }}
                                @endcan
                            </td>
                            <td>
                                {{ $appUser->phone_country ?? '' }}
                                @can('app_user_contact_access')
                                {{ $appUser->phone ?? '' }}
                                @else

                                {{ $appUser->phone ? substr($appUser->phone, 0, -6) . str_repeat('*', 6) : '' }}
                                @endcan

                            </td>

                            <td>
                                <div class="status-toggle d-flex justify-content-between align-items-center">
                                    <input data-id="{{$appUser->id}}" class="check statusdata" type="checkbox"
                                        data-onstyle="success" id="{{'user'. $i++}}" data-offstyle="danger"
                                        data-toggle="toggle" data-on="Active" data-off="InActive" {{ $appUser->status ?
                                    'checked' : '' }} >
                                    <label for="{{'user'. $j++}}" class="checktoggle">checkbox</label>
                                </div>
                            </td>



                            <td>

                                @can('app_user_show')
                                <a class="btn btn-xs btn-primary"
                                    href="{{ route('admin.app-users.show', $appUser->id) }}">
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </a>
                                @endcan

                                @can('app_user_delete')
                                <button type="button" class="btn btn-xs btn-danger delete-button"
                                    data-id="{{ $appUser->id }}">
                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                </button>
                                @endcan
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <nav aria-label="...">
                    <ul class="pagination justify-content-end">
                        {{-- Previous Page Link --}}
                        @if ($appUsers->currentPage() > 1)
                        <li class="page-item">
                            <a class="page-link" href="{{ $appUsers->previousPageUrl() }}" tabindex="-1">{{
                                trans('global.previous') }}</a>
                        </li>
                        @else
                        <li class="page-item disabled">
                            <span class="page-link">{{ trans('global.previous') }}</span>
                        </li>
                        @endif

                        {{-- Numeric Pagination Links --}}
                        @for ($i = 1; $i <= $appUsers->lastPage(); $i++)
                            <li class="page-item {{ $i == $appUsers->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $appUsers->url($i) }}">{{ $i }}</a>
                            </li>
                            @endfor

                            {{-- Next Page Link --}}
                            @if ($appUsers->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $appUsers->nextPageUrl() }}">{{ trans('global.next')
                                    }}</a>
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






<!-- Your custom script -->
@endsection
@include('admin.common.addSteps.footer.footerJs')
@section('scripts')
@parent
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.delete-button');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const appUserId = this.getAttribute('data-id');

                Swal.fire({
                    title: '{{ trans("global.are_you_sure") }}',
                    text: '{{ trans("global.delete_confirmation") }}',
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
                        deleteAppUser(appUserId);
                    }
                });
            });
        });

        function deleteAppUser(appUserId) {
            const url = `{{ route('admin.app-users.destroy', ':id') }}`.replace(':id', appUserId);

            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                success: function (response) {
                    Swal.close();
                    toastr.success('{{ trans('global.delete_app_user') }}', 'Success', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    // Optionally, refresh the page or update UI as needed
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
</script>

<script>
    $(function () {
        let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)

        function handleDeletion(route) {
            return function (e, dt, node, config) {
                var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
                    return $(entry).data('entry-id')
                });

                if (ids.length === 0) {
                    Swal.fire({
                        title: '{{ trans("global.no_entries_selected") }}',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                Swal.fire({
                    title: '{{ trans("global.are_you_sure") }}',
                    text: '{{ trans("global.delete_confirmation") }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var csrfToken = $('meta[name="csrf-token"]').attr('content');
                        $.ajax({
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            method: 'POST',
                            url: route,
                            data: { ids: ids, _method: 'DELETE' }
                        }).done(function (response) {
                            location.reload(); // Reload the page after deletion
                        }).fail(function (xhr, status, error) {
                            Swal.fire(
                                '{{ trans("global.error") }}',
                                '{{ trans("global.delete_error") }}',
                                'error'
                            );
                        });
                    }
                });
            };
        }


        let deleteRoute = "{{ route('admin.app-users.deleteAll') }}";


        let deleteButton = {
            text: '{{ trans("global.delete_all") }}',
            className: 'btn-danger',
            action: handleDeletion(deleteRoute)
        };

        dtButtons.push(deleteButton);

        let table = $('.datatable-AppUser:not(.ajaxTable)').DataTable({ buttons: dtButtons })
        $('a[data-toggle="tab"]').on('shown.bs.tab click', function (e) {
            $($.fn.dataTable.tables(true)).DataTable()
                .columns.adjust();
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab click', function (e) {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });

    })

    // Initialize the Select2 for the customer select box
    $(document).ready(function () {
        // Initialize the Select2 for the customer select box
        $('#customer').select2({
            ajax: {
                url: "{{ route('admin.searchUser') }}",
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    // Transform the response data into Select2 format
                    return {
                        results: $.map(data, function (item) {
                            return {
                                id: item.id,
                                text: item.first_name,
                            };
                        })
                    };
                },
                cache: true, // Cache the AJAX results to avoid multiple requests for the same data
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("Error while fetching customer data:", textStatus, errorThrown);
                    // Optionally display an error message to the user

                }
            }
        });

        var searchfieldId = "{{ $searchfieldId }}"; // Get user ID from the controller adminsearch
        var searchfield = "{{ $searchfield }}"; // Get user name from the controller

        if (searchfieldId) {
            var option = new Option(searchfield, searchfieldId, true, true);
            $('#customer').append(option).trigger('change');
        }
    });


    $(document).ready(function () {
        // Initialize the DateRangePicker
        $('#daterange-btn').daterangepicker({
            opens: 'right', // Change the calendar position to the left side of the input
            autoUpdateInput: false, // Disable auto-update of the input fields
            ranges: {
                'Anytime': [moment(), moment()],
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MM-DD', // Format the date as you need
                separator: ' - ',
                applyLabel: 'Apply',
                cancelLabel: 'Cancel',
                fromLabel: 'From',
                toLabel: 'To',
                customRangeLabel: 'Custom Range'
            }
        });

        // Check if the start and end dates are stored in local storage
        const storedStartDate = localStorage.getItem('selectedStartDate');
        const storedEndDate = localStorage.getItem('selectedEndDate');

        // Get the URL parameters for 'from' and 'to'
        const urlFrom = "{{ request()->input('from') }}";
        const urlTo = "{{ request()->input('to') }}";

        // If both start and end dates are available in local storage, and the URL parameters 'from' and 'to' are not empty, set the initial date range
        if (storedStartDate && storedEndDate && urlFrom && urlTo) {
            const startDate = moment(storedStartDate);
            const endDate = moment(storedEndDate);
            $('#daterange-btn').data('daterangepicker').setStartDate(startDate);
            $('#daterange-btn').data('daterangepicker').setEndDate(endDate);
            $('#daterange-btn').val(startDate.format('YYYY-MM-DD') + ' - ' + endDate.format('YYYY-MM-DD'));
        } else {
            // Otherwise, clear the date range in DateRangePicker
            $('#daterange-btn').val('');
            $('#startDate').val('');
            $('#endDate').val('');

            // Clear the stored start and end dates from local storage
            localStorage.removeItem('selectedStartDate');
            localStorage.removeItem('selectedEndDate');
        }

        // Update the hidden input fields and button text when the date range changes
        $('#daterange-btn').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            $('#startDate').val(picker.startDate.format('YYYY-MM-DD'));
            $('#endDate').val(picker.endDate.format('YYYY-MM-DD'));

            // Store the selected start and end dates in local storage
            localStorage.setItem('selectedStartDate', picker.startDate.format('YYYY-MM-DD'));
            localStorage.setItem('selectedEndDate', picker.endDate.format('YYYY-MM-DD'));
        });

        // Clear the date range selection and input fields when the 'Cancel' button is clicked
        $('#daterange-btn').on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
            $('#startDate').val('');
            $('#endDate').val('');

            // Clear the stored start and end dates from local storage
            localStorage.removeItem('selectedStartDate');
            localStorage.removeItem('selectedEndDate');
        });

        // Function to reset the filters
        function resetFilters() {
            $('#daterange-btn').val('');
            $('#startDate').val('');
            $('#endDate').val('');
            $('#status').val('');
            $('#customer').val('').trigger('change');
        }

        // Optional: Submit the form when the "Filter" button is clicked
        $('button[name="btn"]').on('click', function () {
            $('form').submit();
        });
    });

    $('.statusdata').change(function () {
        var status = $(this).prop('checked') ? 1 : 0;
        var id = $(this).data('id');
        var requestData = {
            'status': status,
            'pid': id,
            '_token': $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            type: "POST",
            dataType: "json",
            url: '/admin/update-appuser-status',
            data: requestData,
            success: function (response) {
                toastr.success(response.message, '{{ trans("global.success") }}', {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-bottom-right"
                });
            },
            error: function (response) {
                if (response.status === 403) {
                    toastr.error(response.responseJSON.message, 'Error', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                } else {
                    toastr.error('Something went wrong. Please try again.', 'Error', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                }
            }
        });
    });

    $('.identify').change(function (e) {
        e.preventDefault();
        var checkbox = $(this);
        var status = checkbox.prop('checked') ? 1 : 0;
        var id = checkbox.data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to verify the Document?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, update it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                var requestData = {
                    'verified': status,
                    'pid': id,
                    '_token': $('meta[name="csrf-token"]').attr('content')
                };

                $('#loader').show(); // optional loader
                $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: '/admin/update-appuser-identify',
                    data: requestData,
                    success: function (response) {
                        toastr.success(response.message, '{{ trans("global.success") }}', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    error: function (response) {
                        toastr.error('Something went wrong. Please try again.', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                        checkbox.prop('checked', !status); // revert if error
                    },
                    complete: function () {
                        $('#loader').hide();
                    }
                });
            } else {
                checkbox.prop('checked', !status); // revert on cancel
            }
        });
    });
    $('.phone_verify').change(function () {
        var status = $(this).prop('checked') == true ? 1 : 0;
        var id = $(this).data('id');
        var requestData = {
            'phone_verify': status,
            'pid': id
        };
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        requestData['_token'] = csrfToken;
        $.ajax({

            type: "POST",
            dataType: "json",
            url: '/admin/update-appuser-phoneverify',
            data: requestData,
            success: function (response) {
                toastr.success(response.message, '{{ trans("global.success") }}', {
                    CloseButton: true,
                    ProgressBar: true,
                    positionClass: "toast-bottom-right"
                });
            }
        });
    })

    $('.email_verify').change(function () {
        var status = $(this).prop('checked') == true ? 1 : 0;
        var id = $(this).data('id');
        var requestData = {
            'email_verify': status,
            'pid': id
        };
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        requestData['_token'] = csrfToken;
        $.ajax({

            type: "POST",
            dataType: "json",
            url: '/admin/update-appuser-emailverify',
            data: requestData,
            success: function (response) {
                toastr.success(response.message, '{{ trans("global.success") }}', {
                    CloseButton: true,
                    ProgressBar: true,
                    positionClass: "toast-bottom-right"
                });
            }
        });
    })

    $('#resetBtn').click(function () {
        $('#appusersFilterForm')[0].reset();
        var baseUrl = '{{ route('admin.app-users.index') }}';
        window.history.replaceState({}, document.title, baseUrl);
        window.location.reload();
    });
    $('.hoststatusdata').change(function (e) {
        e.preventDefault();
        var checkbox = $(this);
        var status = checkbox.prop('checked') ? 1 : 0;
        var id = checkbox.data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to verify the document?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, change it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                var requestData = {
                    'status': status,
                    'pid': id,
                    '_token': $('meta[name="csrf-token"]').attr('content')
                };

                $('#loader').show();
                $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: '/admin/update-appuser-document-status',
                    data: requestData,
                    success: function (response) {
                        toastr.success(response.message, '{{ trans("global.success") }}', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                        // Remove 'Requested' label if status is updated
                        $('.hoststatusdata[data-id="' + id + '"]').closest('.status-toggle').find('.requested-label').remove();
                    },
                    error: function (response) {
                        toastr.error('Something went wrong. Please try again.', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                        checkbox.prop('checked', !status); // revert toggle if error
                    },
                    complete: function () {
                        $('#loader').hide();
                    }
                });
            } else {
                // If cancelled, revert the toggle switch
                checkbox.prop('checked', !status);
            }
        });
    });


    $(document).ready(function () {
        // Bind a click event to the Font Awesome icon
        $('.view-details').on('click', function () {
            var userId = $(this).data('id');
            $('#loader').show();
            // Make an AJAX request to fetch user data
            $.ajax({
                url: "{{ route('admin.get-appuser-host-status-detail') }}", // Use the named route
                method: 'POST',
                data: {
                    user_id: userId,
                    _token: '{{ csrf_token() }}' // Include CSRF token for security
                },
                success: function (response) {
                    console.log(response.data);
                    var defaultImagePath = "{{ asset('images/icon/userdefault.jpg') }}";
                    $('#modal-table tbody').empty();
                    var data = response.data; // Assuming response.data contains the JSON object

                    // Check if phone and country_code exist, and combine them
                    if (data.phone && data.country_code) {
                        data.phone = data.country_code + data.phone;
                        delete data.country_code; // Optionally remove country_code if no longer needed
                    }
                    if (data.phone) {
                        data.phone = data.phone;
                    }

                    // Mask email if it exists
                    if (data.email) {
                        data.email = data.email;
                    }
                    if (data.company_name === null) {
                        data.company_name = ''; // Show blank space if company_name is null
                    }
                    $.each(data, function (key, value) {
                        // Check if the key is related to an image URL
                        if (key === 'image') {
                            var imageUrl = value ? value : defaultImagePath;
                            // Create an image element if the key is 'image'
                            $('#modal-table tbody').append(
                                '<tr>' +
                                '<th>' + capitalizeFirstLetter(key.replace('_', ' ')) + '</th>' +
                                '<td><a href="' + imageUrl + '" target="_blank"><img src="' + imageUrl + '" alt="' + key + '" style="max-width: 200px; height: auto;"></a></td>' +
                                '</tr>'
                            );
                        } else {
                            // Handle other data
                            $('#modal-table tbody').append(
                                '<tr>' +
                                '<th>' + capitalizeFirstLetter(key.replace('_', ' ')) + '</th>' +
                                '<td>' + value + '</td>' +
                                '</tr>'
                            );
                        }
                    });
                    // Show the modal
                    $('#detailsModal').modal('show');

                    // Show the modal
                    // $('#detailsModal').modal('show');
                },
                error: function () {
                    // Handle error
                    console.log('Failed to load user details.');
                },
                complete: function () {
                    // Hide the loader when the request completes
                    $('#loader').hide();
                }
            });
        });

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
    });


    $(document).ready(function () {
        $('.view-verification-documents').on('click', function () {
            var userId = $(this).data('id');
            $('#loader').show();

            $.ajax({
                url: "{{ route('admin.get-verification-documents') }}",
                method: "POST",
                data: { user_id: userId, _token: "{{ csrf_token() }}" },
                success: function (response) {
                    $('#user-documents-modal-table tbody').empty();
                    var defaultImagePath = "{{ asset('images/icon/userdefault.jpg') }}";

                    $.each(response.data.documents, function (key, value) {
                        var imageUrl = value.image || defaultImagePath;
                        var status = capitalizeFirstLetter(value.status || "Pending");
                        var statusColor = getStatusColor(value.status);
                        var isDocumentMissing = !value.status;

                        var buttons = '';
                        if (value.status === 'pending' && !isDocumentMissing) {
                            buttons = `
                            <button class="btn btn-success update-status" data-id="${userId}" data-key="${key}" data-status="approved">Approve</button>
                            <button class="btn btn-danger update-status" data-id="${userId}" data-key="${key}" data-status="rejected">Reject</button>
                        `;
                        }

                        $('#user-documents-modal-table tbody').append(
                            `<tr data-key="${key}">
                            <th>${capitalizeFirstLetter(key.replace(/_/g, " "))}</th>
                            <td><a href="${imageUrl}" target="_blank">
                                <img src="${imageUrl}" alt="${key}" style="max-width: 200px; height: auto;">
                            </a></td>
                            <td><span class="status-label" style="${statusColor}">${status}</span></td>
                            <td>${buttons}</td>
                        </tr>`
                        );
                    });

                    $('#userDocumentsModal').modal('show');
                },
                error: function () {
                    console.error("Failed to load user documents.");
                },
                complete: function () {
                    $('#loader').hide();
                },
            });
        });

        $(document).on("click", ".update-status", function () {
            var userId = $(this).data("id");
            var metaKey = $(this).data("key");
            var status = $(this).data("status");
            var $buttonContainer = $(this).closest('td');

            Swal.fire({
                title: "Are you sure?",
                text: "Do you really want to change the status?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#dc3545",
                confirmButtonText: "Yes, change it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('admin.update-verification-document-status') }}",
                        method: "POST",
                        data: { user_id: userId, meta_key: metaKey, status: status, _token: "{{ csrf_token() }}" },
                        success: function (response) {
                            var statusText = capitalizeFirstLetter(response.status);
                            var statusColor = getStatusColor(response.status);

                            var statusElement = $(`tr[data-key="${metaKey}"] .status-label`);
                            if (statusElement.length) {
                                statusElement.text(statusText).attr("style", statusColor);
                            } else {
                                console.warn("Status element not found for metaKey:", metaKey);
                            }
                            if (response.status === 'approved') {
                                $buttonContainer.find('.btn-success').remove();
                            } else if (response.status === 'rejected') {
                                $buttonContainer.find('.btn-danger').remove();
                            }
                        },
                        error: function (xhr) {
                            Swal.fire({
                                title: "Error!",
                                text: xhr.responseJSON ? xhr.responseJSON.message : "Something went wrong!",
                                icon: "error",
                                confirmButtonText: "OK",
                            });
                        },
                    });
                }
            });
        });

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function getStatusColor(status) {
            return status === "approved"
                ? "background-color: #28a745; color: white; padding: 5px; border-radius: 5px;"
                : status === "rejected"
                    ? "background-color: #dc3545; color: white; padding: 5px; border-radius: 5px;"
                    : "background-color: #ffc107; color: black; padding: 5px; border-radius: 5px;";
        }
    });
</script>
@endsection