<!DOCTYPE html>
<html>

<head>
    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $routeSegments = array_values(array_filter(explode('.', str_replace('admin.', '', (string) $routeName))));
        $routeKey = $routeSegments[0] ?? 'home';
        $resourceLabels = [
            'home' => 'Dashboard',
            'permissions' => 'Permissions',
            'roles' => 'Roles',
            'users' => 'Users',
            'vehicle-type' => 'Vehicle types',
            'vehicle-location' => 'Vehicle locations',
            'vehicle-makes' => 'Vehicle makes',
            'payout-method' => 'Payout methods',
            'cancellation' => 'Cancellation reasons',
            'item-rule' => 'Item rules',
            'drivers' => 'Drivers',
            'driver' => 'Driver profile',
            'app-users' => 'Riders',
            'bookings' => 'Bookings',
            'hire-bookings' => 'QR Hire rides',
            'add-coupons' => 'Coupons',
            'finance' => 'Finance',
            'payouts' => 'Payouts',
            'reviews' => 'Reviews',
            'settings' => 'General settings',
            'email-templates' => 'Notification templates',
            'sos' => 'SOS',
            'recharge-plans' => 'Driver recharge plans',
            'static-pages' => 'Static pages',
            'sliders' => 'Sliders',
            'ticket' => 'Support tickets',
            'report-page' => 'Reports',
        ];
        $sectionLabels = [
            'home' => 'Dashboard',
            'permissions' => 'Admin management',
            'roles' => 'Admin management',
            'users' => 'Admin management',
            'vehicle-type' => 'Platform setup',
            'vehicle-location' => 'Platform setup',
            'vehicle-makes' => 'Platform setup',
            'payout-method' => 'Platform setup',
            'cancellation' => 'Platform setup',
            'item-rule' => 'Platform setup',
            'drivers' => 'Driver management',
            'driver' => 'Driver management',
            'app-users' => 'Rider management',
            'bookings' => 'Ride management',
            'hire-bookings' => 'Ride management',
            'add-coupons' => 'Coupons',
            'finance' => 'Transaction reports',
            'payouts' => 'Transaction reports',
            'reviews' => 'Reviews',
            'settings' => 'Settings',
            'email-templates' => 'Settings',
            'sos' => 'Settings',
            'recharge-plans' => 'Settings',
            'static-pages' => 'Settings',
            'sliders' => 'Settings',
            'ticket' => 'Support tickets',
            'report-page' => 'Reports',
        ];
        $sectionDescriptions = [
            'Dashboard' => 'Track riders, drivers, bookings, and revenue from one control room.',
            'Admin management' => 'Control internal access, permissions, and operator roles.',
            'Platform setup' => 'Configure vehicles, payout methods, and operating rules.',
            'Driver management' => 'Review driver accounts, status, payouts, and compliance.',
            'Rider management' => 'Manage rider accounts, onboarding, and account health.',
            'Ride management' => 'Monitor booking flow, QR Hire activity, and ride states.',
            'Coupons' => 'Manage promotional offers, pricing campaigns, and coupon access.',
            'Transaction reports' => 'Track finance, payouts, approvals, and revenue movement.',
            'Reviews' => 'Review customer feedback and moderation outcomes.',
            'Settings' => 'Manage configuration, templates, static content, and emergency tools.',
            'Support tickets' => 'Handle support queues, issue follow-up, and customer requests.',
            'Reports' => 'Review exported summaries and operational reporting.',
        ];
        $resourceLabel = $resourceLabels[$routeKey] ?? \Illuminate\Support\Str::headline(str_replace('-', ' ', $routeKey));
        $currentArea = $sectionLabels[$routeKey] ?? $resourceLabel;
        $actionSegment = end($routeSegments) ?: 'home';
        $actionLabels = [
            'index' => $resourceLabel,
            'create' => 'Create ' . $resourceLabel,
            'edit' => 'Edit ' . $resourceLabel,
            'show' => $resourceLabel . ' details',
            'trash' => $resourceLabel . ' archive',
        ];
        $currentView = count($routeSegments) > 1
            ? ($actionLabels[$actionSegment] ?? \Illuminate\Support\Str::headline(str_replace('-', ' ', $actionSegment)))
            : $resourceLabel;
        $topbarView = request()->routeIs('admin.home') ? 'Control center' : $currentView;
        $topbarSubtitle = request()->routeIs('admin.home') ? 'Operations workspace' : $currentArea . ' workspace';
        $pageDescription = $sectionDescriptions[$currentArea] ?? 'Manage live operations, configuration, and data from a cleaner control surface.';
        $todayLabel = now()->format('d M Y');
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title> {{ isset($siteName) && $siteName ? $siteName : trans('global.site_title') }}</title>
    <link rel="shortcut icon" href="{{ $faviconPath ?? asset('default/favicon.png') }}" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css"
        rel="stylesheet" />
    <link href="https://use.fontawesome.com/releases/v5.2.0/css/all.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css" rel="stylesheet" />
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css"
        rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.3/css/AdminLTE.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.3/css/skins/_all-skins.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">


    <!-- Ionicons -->
    <link rel="stylesheet" atr="a" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <link type="text/css" href="{{ asset('css/custom.css') }}?{{ time() }}" rel="stylesheet" />
     <link type="text/css" href="{{ asset('css/dashboard.css') }}?{{ time() }}" rel="stylesheet" />
    @yield('styles')

</head>

<body class="sidebar-mini skin-purple admin-theme admin-modern" style="height: auto; min-height: 100%;">
    <div class="wrapper admin-layout-shell" style="height: auto; min-height: 100%;">
        <header class="main-header cvvv">
            <a href="{{ route('admin.home', [], false) }}" class="logo">
                <span class="logo-mini">
                    @if (isset($logoPath) && !empty($logoPath))
                        <img class="admin-logo-image admin-logo-image--mini" src="{{ $logoPath }}" alt="{{ $siteName ?? trans('global.site_title') }}" />
                    @else
                        <b>{{ $siteName ?? trans('global.site_title') }}</b>
                    @endif
                </span>
                <span class="logo-lg">
                    @if (isset($logoPath) && !empty($logoPath))
                        <img class="admin-logo-image admin-logo-image--full" src="{{ $logoPath }}" alt="{{ $siteName ?? trans('global.site_title') }}" />
                    @else
                        {{ $siteName ?? trans('global.site_title') }}
                    @endif
                </span>
            </a>

            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button" aria-label="{{ trans('global.toggleNavigation') }}">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                    <span class="sr-only">{{ trans('global.toggleNavigation') }}</span>
                </a>

                <div class="admin-topbar-copy hidden-xs">
                    <div class="admin-topbar-copy__title">{{ $siteName ?? trans('global.site_title') }}</div>
                    <div class="admin-topbar-copy__subtitle">{{ $topbarSubtitle }}</div>
                </div>

                <div class="admin-topbar-meta hidden-sm hidden-xs">
                    <span class="admin-topbar-pill">{{ $topbarView }}</span>
                    <span class="admin-topbar-pill admin-topbar-pill--subtle">{{ $todayLabel }}</span>
                </div>

                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        @can('language_setting_access')
                            @if (count(config('global.available_languages', [])) > 1)
                                <li class="dropdown notifications-menu admin-language-switch">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-language" aria-hidden="true"></i>
                                        <span>{{ strtoupper(app()->getLocale()) }}</span>
                                        <i class="fas fa-chevron-down admin-language-switch__caret" aria-hidden="true"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <ul class="menu">
                                                @foreach (config('global.available_languages') as $langLocale => $langName)
                                                    <li>
                                                        <a href="{{ url()->current() }}?change_language={{ $langLocale }}">
                                                            {{ strtoupper($langLocale) }} ({{ $langName }})
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            @endif
                        @endcan
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <span class="admin-user-chip">
                                    <span class="admin-user-chip__avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                                    <span class="admin-user-chip__label hidden-xs">{{ auth()->user()->name ?? 'Admin' }}</span>
                                </span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-footer">
                                    <div class="pull-left">
                                        <a href="{{ route('admin.home') }}" class="btn btn-default btn-flat">Dashboard</a>
                                    </div>
                                    <div class="pull-right">
                                        <a href="#" class="btn btn-default btn-flat"
                                            onclick="event.preventDefault(); document.getElementById('logoutform').submit();">
                                            {{ trans('global.logout') }}
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        @include('partials.menu')

        <div class="content-wrapper" style="min-height: 960px;">
            @unless (request()->routeIs('admin.home'))
                <div class="admin-page-intro">
                    <div>
                        <span class="admin-page-intro__eyebrow">{{ $currentArea }}</span>
                        <h1 class="admin-page-intro__title">{{ $currentView }}</h1>
                        <p class="admin-page-intro__subtitle">{{ $pageDescription }}</p>
                    </div>
                    <div class="admin-page-intro__meta">
                        <span>{{ $siteName ?? trans('global.site_title') }} admin</span>
                        <strong>{{ $todayLabel }}</strong>
                    </div>
                </div>
            @endunless
            @if (session('message'))
                <div class="row" style='padding:20px 20px 0 20px;'>
                    <div class="col-lg-12">
                        <div class="alert alert-success" role="alert">{{ session('message') }}</div>
                    </div>
                </div>
            @endif
            @if ($errors->count() > 0)
                <div class="row" style='padding:20px 20px 0 20px;'>
                    <div class="col-lg-12">
                        <div class="alert alert-danger">
                            <ul class="list-unstyled">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            <div class="admin-page-shell">
                @yield('content')
            </div>
        </div>
        <footer class="main-footer text-center">
            <div class="admin-footer-copy">
                &copy; {{ now()->year }} <strong>QRIDES by BAMIRA TRANSPORTATION PRIVATE LIMITED</strong>.
                {{ trans('global.allRightsReserved') }}
            </div>
            <div class="admin-footer-copy admin-footer-copy--muted">
                Designed, developed and maintained by
                <a href="https://rareus.in" target="_blank" rel="noopener noreferrer"><strong>Rareus Private Limited</strong></a>.
            </div>
        </footer>

        <form id="logoutform" action="{{ route('logout') }}" method="POST" style="display: none;">
            {{ csrf_field() }}
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.3/js/adminlte.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.19/js/dataTables.bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.flash.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/16.0.0/classic/ckeditor.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js">
        </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script>
        $(function () {
            let copyButtonTrans = '{{ trans('global.copy') }}'
            let csvButtonTrans = '{{ trans('global.csv') }}'
            let excelButtonTrans = '{{ trans('global.excel') }}'
            let pdfButtonTrans = '{{ trans('global.pdf') }}'
            let printButtonTrans = '{{ trans('global.print') }}'
            let colvisButtonTrans = '{{ trans('global.colvis') }}'
            let selectAllButtonTrans = '{{ trans('global.select_all') }}'
            let selectNoneButtonTrans = '{{ trans('global.deselect_all') }}'

            let languages = {
                'en': 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/English.json',
                'ar': 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/Arabic.json',
                'fr': 'https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json'
            };

            $.extend(true, $.fn.dataTable.Buttons.defaults.dom.button, {
                className: 'btn'
            })
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    url: languages['{{ app()->getLocale() }}']
                },
                columnDefs: [{
                    orderable: false,
                    className: 'select-checkbox',
                    targets: 0
                }, {
                    orderable: false,
                    searchable: false,
                    targets: -1
                }],
                select: {
                    style: 'multi+shift',
                    selector: 'td:first-child'
                },
                order: [],
                scrollX: true,
                pageLength: 100,
                dom: 'lBfrtip<"actions">',
                buttons: [{
                    extend: 'selectAll',
                    className: 'btn-primary',
                    text: selectAllButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    },
                    action: function (e, dt) {
                        e.preventDefault()
                        dt.rows().deselect();
                        dt.rows({
                            search: 'applied'
                        }).select();
                    }
                },
                {
                    extend: 'selectNone',
                    className: 'btn-primary',
                    text: selectNoneButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'copy',
                    className: 'btn-default',
                    text: copyButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    className: 'btn-default',
                    text: csvButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    className: 'btn-default',
                    text: excelButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn-default',
                    text: pdfButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    className: 'btn-default',
                    text: printButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'colvis',
                    className: 'btn-default',
                    text: colvisButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                }
                ]
            });

            $.fn.dataTable.ext.classes.sPageButton = '';
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: '{{ trans('global.areYouSure') }}',
                text: '{{ trans('global.Arewantodeletethis') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ trans('global.yes') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit the form with the specified ID
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }

        $(document).ready(function () {
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
            const storedStartDate = localStorage.getItem('selectedStartDate');
            const storedEndDate = localStorage.getItem('selectedEndDate');
            const urlFrom = "{{ request()->input('from') }}";
            const urlTo = "{{ request()->input('to') }}";
            if (storedStartDate && storedEndDate && urlFrom && urlTo) {
                const startDate = moment(storedStartDate);
                const endDate = moment(storedEndDate);
                $('#daterange-btn').data('daterangepicker').setStartDate(startDate);
                $('#daterange-btn').data('daterangepicker').setEndDate(endDate);
                $('#daterange-btn').val(startDate.format('YYYY-MM-DD') + ' - ' + endDate.format('YYYY-MM-DD'));
            } else {
                $('#daterange-btn').val('');
                $('#startDate').val('');
                $('#endDate').val('');
                localStorage.removeItem('selectedStartDate');
                localStorage.removeItem('selectedEndDate');
            }
            $('#daterange-btn').on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $('#startDate').val(picker.startDate.format('YYYY-MM-DD'));
                $('#endDate').val(picker.endDate.format('YYYY-MM-DD'));
                localStorage.setItem('selectedStartDate', picker.startDate.format('YYYY-MM-DD'));
                localStorage.setItem('selectedEndDate', picker.endDate.format('YYYY-MM-DD'));
            });
            $('#daterange-btn').on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
                $('#startDate').val('');
                $('#endDate').val('');
                localStorage.removeItem('selectedStartDate');
                localStorage.removeItem('selectedEndDate');
            });
            // Retrieve the module ID from localStorage on page load
            var storedModuleId = localStorage.getItem('module_id');
            if (storedModuleId) {
                $('#module_id_input').val(storedModuleId);
            }

            $('.module-popup-item').on('click', function (event) {
                event.preventDefault();

                var moduleId = $(this).data('module-id');
                var moduleUrl = $(this).data('url');
                var filterType = $(this).data('filter');

                // Include the module ID in the requestData object
                var requestData = {
                    'status': '1',
                    'pid': moduleId,
                    'type': 'default_module',
                    'module_id': moduleId // Include module ID here
                };

                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                requestData['_token'] = csrfToken;

                $.ajax({
                    url: '/admin/update-module-status',
                    type: 'POST',
                    data: requestData,
                    success: function (data) {
                        // Set the module ID to the input field
                        $('#module_id_input').val(moduleId);

                        // Store the module ID in localStorage
                        localStorage.setItem('module_id', moduleId);

                        // On success, reload the page
                        location.reload();
                    },
                    error: function (xhr, status, error) {
                        // Handle the error
                        console.error('Error: ' + status);
                    }
                });
            });
        });
   
        $(document).ready(function () {
            @if(session('error'))
                toastr.error("{{ session('error') }}", 'Error', {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-bottom-right"
                });
            @endif

             @if(session('success'))
                toastr.success("{{ session('success') }}", 'Success', {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-bottom-right"
                });
            @endif
    });

        (function() {
            const header = document.querySelector('body.admin-modern .main-header');
            if (!header) {
                return;
            }

            let lastScrollY = window.scrollY;

            function syncHeaderVisibility() {
                const currentScrollY = window.scrollY;

                if (currentScrollY <= 24) {
                    header.classList.remove('admin-header-hidden');
                } else {
                    const scrollingDown = currentScrollY > lastScrollY;
                    header.classList.toggle('admin-header-hidden', scrollingDown);
                }

                lastScrollY = currentScrollY;
            }

            window.addEventListener('scroll', syncHeaderVisibility, { passive: true });
            syncHeaderVisibility();
        })();
    </script>
 @yield('scripts')
       <script src="{{ asset('js/resources/main.js') }}?{{ time() }}"></script>

</body>

</html>
