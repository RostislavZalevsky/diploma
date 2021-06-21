@extends('layout')

@section('title', "Reset password")

@section('content')
    <div class="container mt-5">
        <form class="row g-3" method="post" action="{{ route('password.reset') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            <legend>Reset password</legend>
            <div class="mb-3 row">
                <label for="password" class="col-sm-2 col-form-label">Password</label>
                <div class="col-sm-10">
                    <input required minlength="6" type="password" class="form-control" id="password" name="password">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="password_confirmation" class="col-sm-2 col-form-label">Confirm Password</label>
                <div class="col-sm-10">
                    <input required minlength="6" type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Reset</button>
                <p>Don't have an account? <a href="{{ route('register.index') }}" class="link-primary">Sign up</a> or <a href="{{ route('login.index') }}" class="link-primary">Sign in</a></p>
            </div>
        </form>
    </div>
@endsection