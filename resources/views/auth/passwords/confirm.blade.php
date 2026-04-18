@extends('layouts.app')
@section('content')
<div class="login-box reset">
    <div class="login-logo">
        <a href="{{ route('admin.home') }}">
            {{ trans('global.site_title') }}
        </a>
    </div>
    <div class="login-box-body">
        <p class="login-box-msg">
            {{ __('Please confirm your password before continuing.') }}
        </p>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password" placeholder="{{ trans('global.login_password') }}">

                @if ($errors->has('password'))
                    <span class="help-block" role="alert">
                        {{ $errors->first('password') }}
                    </span>
                @endif
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <button type="submit" class="btn btn-primary btn-flat btn-block">
                        {{ __('Confirm Password') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
