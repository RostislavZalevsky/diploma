@extends('layout')

@section('title', "Home")

@section('content')
    <div class="container">
        <div class="row">
            <img style="max-height: 500px; max-width: 500px;" src="{{ asset('img/img.png') }}" class="rounded mx-auto d-block">
        </div>
        <div class="row text-center">
            <h1>Welcome to Company</h1>
            <div>
                <a href="{{ route('prices.index') }}" class="btn btn-primary text-uppercase"><b>Get now</b></a>
            </div>
        </div>
    </div>
@endsection