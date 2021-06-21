@extends('layout')

@section('title', "Reset password")

@section('content')
    <div class="container mt-5">
        <form class="row g-3" method="post" action="{{ route('password.forgot') }}">
            @csrf
            <legend>Forgot password</legend>
            <div class="mb-3 row">
                <label for="email" class="col-sm-2 col-form-label">Email</label>
                <div class="col-sm-10">
                    <input required type="email" class="form-control" id="email" name="email" {{ (old('email') ? ('value=' . old('email')) : 'autofocus') }}>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Send password reset link</button>
                <p>Don't have an account? <a href="{{ route('register.index') }}" class="link-primary">Sign up</a> or <a href="{{ route('login.index') }}" class="link-primary">Sign in</a></p>
            </div>
        </form>
    </div>
@endsection