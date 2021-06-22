@extends('layout')

@section('title', "Subscribed")

@section('content')
    <h1 class="text-center m-5 font-weight-bold">Thank you for using our application!</h1>
    <div class="container">
        <div class="row m-3">
            <h3>Subscription Details:</h3>
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

        <div class="row m-3 text-center">
            <form action="{{ route('subscription.cancel') }}" method="POST" class="form">
                @method('delete')
                @csrf
                <button class="btn btn-danger">Unsubscribe</button>
            </form>
        </div>

        <div class="row">
            <h3 class="row">Billing Details:</h3>
            <div class="row">
                <div class="col-12 col-lg-3"><b>Date</b></div>
                <div class="col-12 col-lg-3 text-center"><b>Total</b></div>
                <div class="col-12 col-lg-3 text-center"><b>Status</b></div>
                <div class="col-12 col-lg-3 text-right"><b>Receipt</b></div>
            </div>

            @isset ($transactions)
                @foreach ($transactions as $transaction)
                    <div class="row">
                        <div class="col-12 col-lg-3">{{ convertFormatDate($transaction->paid_at) }}</div>
                        <div class="col-12 col-lg-3 text-center">{{ number_format($transaction->amount, 2) }} USD</div>
                        <div class="col-12 col-lg-3 text-center">{{ formatPaymentStatus($transaction->payment_status) }}</div>
                        @isset ($transaction->receipt)
                            <div class="col-12 col-lg-3 text-right">
                                <a href="{{ $transaction->receipt }}" class="billing-table__receipt" target="_blank">View</a>
                            </div>
                        @endisset
                    </div>
                @endforeach
            @else
                <div class="row">
                    <div class="col">No Data.</div>
                </div>
            @endisset
            {{ json_encode($transactions) }}
        </div>
    </div>
@endsection