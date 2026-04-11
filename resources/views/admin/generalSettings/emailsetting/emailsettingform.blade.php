@extends('layouts.admin')
@section('content')

<section class="content">
    <div class="row">
        <div class="col-md-3 settings_bar_gap">
            <div class="box box-info box_info">
                <div class="">
                    <h4 class="all_settings f-18 mt-1" style="margin-left:15px;">{{ trans('global.manage_settings') }}
                    </h4>
                    @include('admin.generalSettings.general-setting-links.links')
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="box box-info">

                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('global.emailSettings_title_singular') }}</h3><span
                        class="email_status" style="display: none;">(<span class="text-green"><i class="fa fa-check"
                                aria-hidden="true"></i>Verified</span>)</span>
                </div>

                <form id="email_setting" method="post" action="{{ route('admin.addemailwizard') }}"
                    class="form-horizontal " novalidate="novalidate">
                    {{ csrf_field() }}
                    <div class="box-body">

                        <div class="form-group password" style="display: block;">
                            <label for="inputEmail3" class="col-sm-3 control-label">{{ trans('global.host') }} <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-6">
                                <input type="text" name="host" class="form-control " id="host" placeholder="Host"
                                    value="{{ $host->meta_value ?? '' }}">
                                <span class="text-danger"></span>
                            </div>
                            <div class="col-sm-3">
                                <small></small>
                            </div>
                        </div>
                        <div class="form-group password" style="display: block;">
                            <label for="inputEmail3" class="col-sm-3 control-label">{{ trans('global.port') }} <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-6">
                                <input type="text" name="port" class="form-control " id="port" placeholder="Port"
                                    value="{{ $port->meta_value ?? '' }}">
                                <span class="text-danger"></span>
                            </div>
                            <div class="col-sm-3">
                                <small></small>
                            </div>
                        </div>
                        <div class="form-group password" style="display: block;">
                            <label for="inputEmail3" class="col-sm-3 control-label">{{ trans('global.user_name') }}
                                <span class="text-danger">*</span></label>
                            <div class="col-sm-6">
                                <input type="text" name="username" class="form-control " id="username"
                                    placeholder="User Name" value="{{ $username->meta_value ?? '' }}">
                                <span class="text-danger"></span>
                            </div>
                            <div class="col-sm-3">
                                <small></small>
                            </div>
                        </div>
                        <div class="form-group password" style="display: block;">
                            <label for="inputEmail3" class="col-sm-3 control-label">{{ trans('global.password') }} <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-6">
                                <input type="password" name="password" class="form-control " id="password"
                                    placeholder="Password" value="{{ $password->meta_value ?? '' }}">
                                <span class="text-danger"></span>
                            </div>
                            <div class="col-sm-3">
                                <small></small>
                            </div>
                        </div>
                        <div class="form-group password" style="display: block;">
                            <label for="inputEmail3" class="col-sm-3 control-label">{{ trans('global.encryption') }}
                                <span class="text-danger">*</span></label>
                            <div class="col-sm-6">
                                <input type="text" name="encryption" class="form-control " id="encryption"
                                    placeholder="Encryption" value="{{ $encryption->meta_value ?? '' }}">
                                <span class="text-danger"></span>
                            </div>
                            <div class="col-sm-3">
                                <small></small>
                            </div>
                        </div>
                        <div class="form-group password" style="display: block;">
                            <label for="inputEmail3" class="col-sm-3 control-label">{{ trans('global.from_email') }}
                                <span class="text-danger">*</span></label>
                            <div class="col-sm-6">
                                <input type="email" name="from_email" class="form-control " id="from_email"
                                    placeholder="Mail From Address" value="{{ $from_email->meta_value ?? '' }}">
                                <span class="text-danger"></span>
                            </div>
                            <div class="col-sm-3">
                                <small></small>
                            </div>
                        </div>
                        <input type="hidden" name="emailwizard_email_status" class="form-control email_status_check"
                            id="email_status" value="0" placeholder="Email Status">
                        <div class="text-center" id="error-message"></div>
                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-info btn-space">{{ trans('global.save') }}</button>
                        <a class="btn btn-danger" href="{{ route('admin.settings') }}">{{ trans('global.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection
