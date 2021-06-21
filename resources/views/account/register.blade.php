@extends('layout')

@section('title', "Register")

@section('content')
    <div class="container mt-5">
        <form class="row g-3" method="post" action="{{ route('register') }}">
            @csrf
            <legend>Registration</legend>
            <div class="mb-3 row">
                <label for="name" class="col-sm-2 col-form-label">Fullname</label>
                <div class="col-sm-10">
                    <input required min="3" type="text" class="form-control" id="name" name="name" {{ (old('name') ? ('value=' . old('name')) : 'autofocus') }}>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="email" class="col-sm-2 col-form-label">Email</label>
                <div class="col-sm-10">
                    <input required type="email" class="form-control" id="email" name="email" value='{{ old('email') }}'>
                </div>
            </div>
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
            <div class="mb-3">
                <input type="checkbox" class="form-check-input" id="agree" name="agree">
                <label class="form-check-label" for="agree">By creating this account, I agree to the following this company <a href="#" class="link-primary">Terms & Conditions</a> & <a href="#" class="link-primary">Privacy Policy</a></label>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Sign up</button>
                <p>Already a member? <a href="{{ route('login.index') }}" class="link-primary">Sign in</a></p>
            </div>
        </form>
    </div>
@endsection