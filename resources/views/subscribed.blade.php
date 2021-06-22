@extends('layout')

@section('title', "Subscribed")

@section('content')
    <h1 class="text-center m-5 font-weight-bold">Thank you for using our application!</h1>
    <div class="container">
        <div class="row">
            <h3 class="row">Subscription Details:</h3>
            @isset($subscriber['name'])
            <div>Name: {{ $subscriber['name'] }}</div>
            @endisset
            @isset($subscriber['email'])
            <div>Email: {{ $subscriber['email'] }}</div>
            @endisset
            @isset($subscriber['card'])
                <div>{{ ucwords($subscriber['card']['funding']) }} Card: {{ ucwords($subscriber['card']['brand']) }} *{{ $subscriber['card']['last4'] }} {{ $subscriber['card']['exp_month'] }}/{{ $subscriber['card']['exp_year'] }}</div>
            @endisset
            <div>Price: ${{ number_format($subscriber['price'], 2) }} per {{ $subscription->interval }}</div>
            <div>Next payment date: {{ convertFormatDate($subscriber['next_payment_date']) }}</div>
        </div>

        <div class="row">
            <form action="{{ route('subscription.cancel') }}" method="POST" class="form">
                @method('delete')
                @csrf
                <button class="btn btn-danger">Unsubscribe</button>
            </form>
        </div>
    </div>
@endsection