@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('global.addCoupon_title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.add-coupons.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('global.id') }}
                                    </th>
                                    <td>
                                        {{ $addCoupon->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('global.coupon_titles') }}
                                    </th>
                                    <td>
                                        {{ $addCoupon->coupon_title }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('global.coupon_expiry_date') }}
                                    </th>
                                    <td>
                                        {{ $addCoupon->coupon_expiry_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('global.coupon_code') }}
                                    </th>
                                    <td>
                                        {{ $addCoupon->coupon_code }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('global.coupon_value') }}
                                    </th>
                                    <td>
                                        {{ $addCoupon->coupon_value }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('global.status') }}
                                    </th>
                                    <td>
                                        {{ App\Models\AddCoupon::STATUS_SELECT[$addCoupon->status] ?? '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.add-coupons.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection