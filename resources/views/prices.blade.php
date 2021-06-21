@extends('layout')

@section('title', "Prices")

@section('content')
    <h1 class="text-center m-5 font-weight-bold">Prices</h1>
    <div class="container">
        <div class="row text-center">
            <div class="col"></div>
            <h3 class="col text-uppercase">MONTHLY PREMIUM</h3>
            <h3 class="col text-uppercase">YEARLY PREMIUM</h3>
        </div>
        <div class="row text-center">
            <div class="col"></div>
            <h3 class="col text-danger">${{ $premium_month }}</h3>
            <h3 class="col text-danger">${{ $premium_year }}</h3>
        </div>
        <div class="row text-center">
            <div class="col"></div>
            <div class="col"><a href="{{ route('subscription.index') }}" class="btn btn-danger">Start for free</a></div>
            <div class="col"><a href="{{ route('subscription.index') }}" class="btn btn-danger">Start for free</a></div>
        </div>
        <div class="row text-center border-top h3 py-2 mt-2 mb-0">
            <div class="col">Free trial</div>
            <div class="col">7 days</div>
            <div class="col">7 days</div>
        </div>
        <div class="row text-center border-top border-bottom h3 py-2">
            <div class="col">Savings per year</div>
            <div class="col">–</div>
            <div class="col">${{ (12 * $premium_month) - $premium_year }}</div>
        </div>
    </div>
@endsection