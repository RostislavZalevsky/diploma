@extends('layout')

@section('title', "Login")

@section('content')
    <div class="container mt-5">
        <form class="row g-3" method="post" action="{{ route('login') }}">
            @csrf
            <legend>Authorization</legend>
            <div class="mb-3 row">
                <label for="email" class="col-sm-2 col-form-label">Email</label>
                <div class="col-sm-10">
                    <input required type="email" class="form-control" id="email" name="email" {{ (old('email') ? ('value=' . old('email')) : 'autofocus') }}>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="password" class="col-sm-2 col-form-label">Password</label>
                <div class="col-sm-10">
                    <input required minlength="6" type="password" class="form-control" id="password" name="password">
                    <a href="{{ route('password.index') }}" class="link-primary">Forgot password?</a>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Sign in</button>
                <p>Don't have an account? <a href="{{ route('register.index') }}" class="link-primary">Sign up</a></p>
            </div>
        </form>
    </div>
@endsection