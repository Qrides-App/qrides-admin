@extends('layouts.admin')
@section('styles')
@parent
<style>
    .vehicle-makes-page .page-action-row {
        margin-bottom: 18px;
    }

    .vehicle-makes-page .btn-add-make {
        border-radius: 16px;
        padding: 12px 18px;
        font-weight: 700;
    }

    .vehicle-makes-page .filter-card,
    .vehicle-makes-page .table-card {
        border: 0;
        border-radius: 24px;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .vehicle-makes-page .filter-card .box-body,
    .vehicle-makes-page .table-card .panel-body {
        padding: 26px 28px;
    }

    .vehicle-makes-page .table-card .panel-heading {
        border: 0;
        background: #fff;
        font-size: 18px;
        font-weight: 700;
        padding: 22px 28px 8px;
    }

    .vehicle-makes-page .filter-label {
        display: block;
        margin-bottom: 10px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
    }

    .vehicle-makes-page .filter-actions {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        height: 100%;
    }

    .vehicle-makes-page .filter-actions .btn {
        min-width: 110px;
        border-radius: 14px;
        font-weight: 600;
    }

    .vehicle-makes-page .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }

    .vehicle-makes-page .dt-buttons .btn {
        border-radius: 14px;
        font-weight: 600;
        box-shadow: none;
    }

    .vehicle-makes-page .dt-buttons .btn-danger,
    .vehicle-makes-page .dt-buttons .btn-outline-danger {
        background: #fff5f5;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .vehicle-makes-page .dataTables_filter input,
    .vehicle-makes-page .dataTables_length select,
    .vehicle-makes-page #typeId {
        border-radius: 14px;
        min-height: 46px;
    }

    .vehicle-makes-page .type-badge-muted {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="content vehicle-makes-page">

@can($permissionrealRoute.'_create')
        <div class="row page-action-row">
            <div class="col-lg-12">
                <a class="btn btn-primary btn-add-make" href="{{ route($createRoute) }}">
                    {{ trans('global.add') }} {{$title}}
                </a>
            </div>
        </div>
        @endcan
        <div class="row">
        <div class="col-lg-12">
            <div class="box filter-card">
                <div class="box-body">
                    <form class="form-horizontal" id="propertyFilterForm" action="{{ route($indexRoute) }}" method="GET" accept-charset="UTF-8">
                        <div class="row" style="display:flex;align-items:flex-end;row-gap:16px;">
                            <div class="col-md-3 col-sm-12 col-xs-12">
                                <label class="filter-label">Type</label>
                                <select class="form-control select2" name="typeId" id="typeId">
                                    <option value="">{{ trans('global.pleaseSelect') }}</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}" {{ request('typeId') == $type->id ? 'selected' : '' }} >{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-12 col-xs-12">
                                <div class="filter-actions">
                                <button type="submit" name="btn" class="btn btn-primary btn-flat filterproduct">{{ trans('global.filter') }}</button>
                                <button type="button" id="resetBtn" class="btn btn-default btn-flat resetproduct">{{ trans('global.reset') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default table-card">
                <div class="panel-heading">
                    {{ $title }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-vehicleMake">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('global.id') }}
                                </th>
                                <th>
                                    {{ trans('global.name') }}
                                </th>
                                <th>
                                    {{ trans('global.description') }}
                                </th>
                                <th>
                                    {{ trans('global.status') }}
                                </th>
                                <th>
                                    {{ trans('global.type') }}
                                </th>
                                <th>
                                    &nbsp;
                                </th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection
@section('scripts')
@parent
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
  let deleteButton = {
    text: '{{ trans("global.delete_all") }}',
    url: "{{ route('admin.vehicle-makes.deleteAll') }}", // Replace with your delete route
    className: 'btn-outline-danger',
    action: function (e, dt, node, config) {
        var ids = $.map(dt.rows({ selected: true }).data(), function (entry) {
            return entry.id;
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

        // Use SweetAlert for confirmation
        Swal.fire({
            title: '{{ trans("global.are_you_sure") }}',
            text: '{{ trans("global.delete_confirmation") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    headers: { 'x-csrf-token': csrfToken },
                    method: 'POST',
                    url: config.url,
                    data: { ids: ids, _method: 'DELETE' }
                }).done(function () {
                    Swal.fire(
                        '{{ trans("global.deleted") }}',
                        '{{ trans("global.entries_deleted") }}',
                        'success'
                    );
                    dt.ajax.reload();
                }).fail(function (xhr, status, error) {
                    Swal.fire(
                        '{{ trans("global.error") }}',
                        '{{ trans("global.delete_error") }}',
                        'error'
                    );
                });
            }
        });
    }
};

        dtButtons.push(deleteButton)
  
  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: {
        url: "{{ route($indexRoute) }}",
        type: 'GET',
        data: function(d) {
                d.typeId = $('#typeId').val();
            }
    },
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'name', name: 'name' },
{ data: 'description', name: 'description' },

{ 
    data: 'status',
      name: 'status',
      render: function (data, type, row) {
        return `
          <div class="status-toggle d-flex justify-content-between align-items-center">
            <input
              data-id="${row.id}"
              class="check statusdata"
              type="checkbox"
              data-onstyle="success"
              id="${'user' + row.id}"
              data-offstyle="danger"
              data-toggle="toggle"
              data-on="Active"
              data-off="InActive"
              ${data ? 'checked' : ''}
            >
            <label for="${'user' + row.id}" class="checktoggle">checkbox</label>
          </div>
        `;
      },
      createdCell: function (td, cellData, rowData, row, col) {
        // Add an event listener for the toggle change event
        $(td).on('change', '.statusdata', function () {
          var status = $(this).prop('checked') ? 1 : 0;
          var id = rowData.id;

          var requestData = {
            'status': status,
            'pid': id
          };

          var csrfToken = $('meta[name="csrf-token"]').attr('content');
          requestData['_token'] = csrfToken;

          $.ajax({
            type: "POST",
            dataType: "json",
            url: '{{$ajaxUpdate}}', // Replace with your actual URL
            data: requestData,
            success: function (response) {
              toastr.success(response.message, '{{ trans("global.success") }}', {
                CloseButton: true,
                ProgressBar: true,
                positionClass: "toast-bottom-right"
              });
              // Update the label's 'active' class based on the status
              var label = $(td).find('label.checktoggle');
              if (status === 1) {
                label.addClass('active');
              } else {
                label.removeClass('active');
              }
            },
            error: function(response) {
                if(response.status === 403) {
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
      }
    },
    {
      data: 'typeName',
      name: 'typeName',
      orderable: false,
      searchable: false,
      render: function (data) {
        return data && data.trim()
          ? data
          : '<span class="type-badge-muted">Not linked</span>';
      }
    },

{ 


data: 'actions',
        name: '{{ trans('global.actions') }}',
        orderable: false,
        searchable: false
      },
    ],
    orderCellsTop: true,
    order: [[1, 'desc']],
    pageLength: 100,
  };

  let table = $('.datatable-vehicleMake').DataTable(dtOverrideGlobals);

  $('#typeId').on('change', function() {
        $('#propertyFilterForm').submit();
    });
    $('#resetBtn').on('click', function() {
        
        $('#typeId').val('').trigger('change');
        
        $('#propertyFilterForm').submit();
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $($.fn.dataTable.tables(true)).DataTable()
            .columns.adjust()
            .responsive.recalc();
    });
    


  // Enable row selection
   table.on('click', 'tr', function () {
            $(this).toggleClass('selected');
        });
  
});

</script>
@endsection
