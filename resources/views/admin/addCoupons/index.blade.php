@extends('layouts.admin')

@section('content')
    @php
        $toggleIndex = 0;
        $activeCoupons = $addCoupons->where('status', '1')->count();
        $expiredCoupons = $addCoupons->filter(fn($coupon) => !empty($coupon->getRawOriginal('coupon_expiry_date')) && \Carbon\Carbon::parse($coupon->getRawOriginal('coupon_expiry_date'))->isPast())->count();
    @endphp
    <div class="content">
        @can('add_coupon_create')
            <div class="row" style="margin-bottom: 16px;">
                <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
                    <div>
                        <h4 style="margin: 0 0 4px;">Coupon Campaigns</h4>
                        <p class="text-muted" style="margin: 0;">Manage rider promo codes, first-booking offers, and usage rules from one place.</p>
                    </div>
                    <a class="btn btn-success" href="{{ route('admin.add-coupons.create') }}">
                        {{ trans('global.add') }} {{ trans('global.addCoupon_title_singular') }}
                    </a>
                </div>
            </div>
        @endcan

        <div class="row" style="margin-bottom: 16px;">
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="text-muted small">Total coupons</div>
                        <div style="font-size: 28px; font-weight: 700;">{{ $addCoupons->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="text-muted small">Active coupons</div>
                        <div style="font-size: 28px; font-weight: 700; color: #15803d;">{{ $activeCoupons }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="text-muted small">Expired coupons</div>
                        <div style="font-size: 28px; font-weight: 700; color: #b91c1c;">{{ $expiredCoupons }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>{{ trans('global.addCoupon_title_singular') }} {{ trans('global.list') }}</strong>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table-bordered table-striped table-hover datatable datatable-AddCoupon table">
                                <thead>
                                    <tr>
                                        <th width="10"></th>
                                        <th>ID</th>
                                        <th>Offer</th>
                                        <th>Value</th>
                                        <th>Eligibility</th>
                                        <th>Usage Rules</th>
                                        <th>Expiry</th>
                                        <th>Status</th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($addCoupons as $addCoupon)
                                        @php
                                            $expiryRaw = $addCoupon->getRawOriginal('coupon_expiry_date');
                                            $isExpired = !empty($expiryRaw) && \Carbon\Carbon::parse($expiryRaw)->isPast();
                                            $currentImage = $addCoupon->getFirstMediaUrl('coupon_image', 'thumb') ?: $addCoupon->getFirstMediaUrl('coupon_image');
                                        @endphp
                                        <tr data-entry-id="{{ $addCoupon->id }}" class="{{ $isExpired ? 'danger' : '' }}">
                                            <td></td>
                                            <td>{{ $addCoupon->id }}</td>
                                            <td>
                                                <div class="d-flex align-items-start" style="gap: 12px; min-width: 260px;">
                                                    @if ($currentImage)
                                                        <img src="{{ $currentImage }}" alt="Coupon image" style="width: 52px; height: 52px; border-radius: 12px; object-fit: cover; border: 1px solid #e5e7eb;">
                                                    @else
                                                        <div style="width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #eff6ff; color: #2563eb;">
                                                            <i class="fas fa-ticket-alt"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div style="font-weight: 700;">{{ $addCoupon->coupon_title }}</div>
                                                        @if (!empty($addCoupon->coupon_subtitle))
                                                            <div class="text-muted small" style="margin: 2px 0 6px;">{{ $addCoupon->coupon_subtitle }}</div>
                                                        @endif
                                                        <div>
                                                            <span class="label label-default" style="font-size: 12px;">{{ $addCoupon->coupon_code }}</span>
                                                            @if ($firstBookingCoupon === $addCoupon->coupon_code)
                                                                <span class="label label-success" style="font-size: 12px; margin-left: 6px;">First booking</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong>{{ rtrim(rtrim(number_format((float) $addCoupon->coupon_value, 2, '.', ''), '0'), '.') }}%</strong>
                                            </td>
                                            <td>
                                                @if (!empty($addCoupon->min_order_amount))
                                                    Minimum ride fare:
                                                    <strong>{{ $general_default_currency->meta_value ?? '' }} {{ rtrim(rtrim(number_format((float) $addCoupon->min_order_amount, 2, '.', ''), '0'), '.') }}</strong>
                                                @else
                                                    <span class="text-muted">No minimum fare rule</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div><strong>Total:</strong> {{ $addCoupon->max_uses ?: 'Unlimited' }}</div>
                                                <div><strong>Per user:</strong> {{ $addCoupon->max_uses_per_user ?: 'Unlimited' }}</div>
                                            </td>
                                            <td>
                                                @if ($expiryRaw)
                                                    <div>{{ \Carbon\Carbon::parse($expiryRaw)->format('d M Y') }}</div>
                                                    @if ($isExpired)
                                                        <span class="label label-danger" style="margin-top: 6px; display: inline-block;">Expired</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">No expiry</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="status-toggle d-flex justify-content-between align-items-center">
                                                    <input data-id="{{ $addCoupon->id }}" class="check statusdata"
                                                        type="checkbox" id="coupon_status_{{ $toggleIndex }}"
                                                        data-toggle="toggle" data-on="Active" data-off="InActive"
                                                        {{ $addCoupon->status ? 'checked' : '' }}>
                                                    <label for="coupon_status_{{ $toggleIndex }}" class="checktoggle">checkbox</label>
                                                </div>
                                                @php $toggleIndex++; @endphp
                                            </td>
                                            <td>
                                                @can('add_coupon_edit')
                                                    <a class="btn btn-xs btn-info" href="{{ route('admin.add-coupons.edit', $addCoupon->id) }}">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                @endcan

                                                @can('add_coupon_delete')
                                                    <form action="{{ route('admin.add-coupons.destroy', $addCoupon->id) }}" method="POST" style="display: inline-block;">
                                                        @method('DELETE')
                                                        @csrf
                                                        <button type="button" class="btn btn-xs btn-danger delete-button" data-id="{{ $addCoupon->id }}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script>
        $(function() {
            let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons);

            @can('add_coupon_delete')
                let deleteButton = {
                    text: '{{ trans('global.delete') }}',
                    url: "{{ route('admin.add-coupons.massDestroy') }}",
                    className: 'btn-danger',
                    action: function(e, dt, node, config) {
                        const ids = $.map(dt.rows({ selected: true }).nodes(), function(entry) {
                            return $(entry).data('entry-id');
                        });

                        if (ids.length === 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: '{{ trans('global.zero_selected') }}',
                                showConfirmButton: false,
                                timer: 1500
                            });
                            return;
                        }

                        Swal.fire({
                            title: '{{ trans('global.are_you_sure') }}',
                            text: '{{ trans('global.adddelete_confirmation') }}',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '{{ trans('global.yes_delete') }}'
                        }).then((result) => {
                            if (!result.isConfirmed) {
                                return;
                            }

                            $.ajax({
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                method: 'POST',
                                url: config.url,
                                data: {
                                    ids: ids,
                                    _method: 'DELETE'
                                }
                            }).done(function() {
                                Swal.fire({
                                    icon: 'success',
                                    title: '{{ trans('global.deleted') }}',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => location.reload());
                            }).fail(function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ trans('global.error') }}',
                                    text: '{{ trans('global.delete_error') }}'
                                });
                            });
                        });
                    }
                };

                dtButtons.push(deleteButton);
            @endcan

            $.extend(true, $.fn.dataTable.defaults, {
                orderCellsTop: true,
                order: [[1, 'desc']],
                pageLength: 10,
            });

            $('.datatable-AddCoupon:not(.ajaxTable)').DataTable({
                buttons: dtButtons
            });

            $('a[data-toggle="tab"]').on('shown.bs.tab click', function() {
                $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
            });
        });
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        $('.statusdata').change(function() {
            const status = $(this).prop('checked') ? 1 : 0;
            const id = $(this).data('id');

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: '/admin/update-addCoupon-status',
                data: {
                    status: status,
                    pid: id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 200) {
                        toastr.success(response.message, '{{ trans('global.success') }}', {
                            closeButton: true,
                            progressBar: true,
                        });
                    }
                }
            });
        });

        $(document).on('click', '.delete-button', function() {
            const deleteForm = $(this).closest('form');

            Swal.fire({
                title: '{{ trans('global.are_you_sure') }}',
                text: "{{ trans('global.adddelete_confirmation') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ trans('global.yes_delete') }}',
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteForm.submit();
                }
            });
        });
    </script>
@endsection
