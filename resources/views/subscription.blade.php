@extends('layout')

@section('title', "Subscription")

@section('content')
    <h1 class="text-center m-5 font-weight-bold">Subscription</h1>
    <form class="container">
        <div class="row">
            <div class="mb-3">
                <label for="period" class="form-label">Choose a period:</label>
                <select id="period" name="period" class="form-select">
                    <option value="month">${{ $premium_month }} per month</option>
                    <option value="year">${{ $premium_year }} per year</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="method" class="form-label">Payment method:</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="method" id="stripe" checked value="card">
                    <label class="form-check-label" for="stripe">Debit/Credit Card</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="method" id="paypal" value="paypal">
                    <label class="form-check-label" for="paypal">PayPal</label>
                </div>
            </div>

            <div class="col-12 text-center">
                <!-- Stripe -->
                <div id="credit-card" class="credit-card__number"></div>
                <div id="credit-card-errors" class="credit-card__errors text-danger" role="alert"></div>

                <!-- PayPal -->
                <div id="paypal-payment" class="d-none paypal-payment hidden"></div>
            </div>

            <div class="col-12 text-center">
                <button id="subscribe-button" type="button" class="btn btn-primary">Subscribe</button>
            </div>
        </div>
    </form>
@endsection

@section('before_js')
    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypal_client_id }}&vault=true&intent=subscription"></script>
    <script src="https://js.stripe.com/v3/"></script>
@endsection

@section('js')
    <script>
        // --- PayPal System
        paypal.Buttons({
            createSubscription: function (data, actions) {
                return actions.subscription.create({
                    'plan_id': createPaymentSubscription({
                        period: $('[name="period"]').val(),
                        paymentMethod: 'PayPal',
                    }), // Creates the subscription
                    'application_context': {
                        'shipping_preference': 'NO_SHIPPING'
                    }
                });
            },
            onApprove: function (data, actions) {
                set_subscription('PayPal', data.subscriptionID);
            }
        }).render('#paypal-payment'); // Renders the PayPal button

        // --- Stripe System
        var stripe = Stripe('{{ $stripe_client_id }}');
        var elements = stripe.elements();

        var style = {
            base: {
                // Add your base input styles here. For example:
                fontSize: '20px',
                color: '#32325d',
            },
        };

        var card = elements.create('card', { style: style });

        card.mount('#credit-card');

        card.on('change', function (event) {
            displayError(event);
        });

        function displayError(event) {
            $('#credit-card-errors').html(event.error.message);

            return event.error;
        }

        $('#subscribe-button').on('click', function () {
            create_payment_method(elements.getElement('card'));
        });

        function create_payment_method(card_element) {
            // Set up payment method for recurring usage
            stripe.createPaymentMethod({
                type: 'card',
                card: card_element,
                billing_details: {
                    name: null
                }
            }).then((result) => {
                if (result.error) {
                    displayError(result);
                } else {
                    var result_created_subscription = createPaymentSubscription({
                        period: $('[name="period"]').val(),
                        paymentMethod: 'Stripe',
                        paymentMethodId: result.paymentMethod.id
                    });

                    if (!result_created_subscription.success) displayError(result_created_subscription.message);

                    handle_payment_that_requires_customer_action({
                        subscription: result_created_subscription.content.subscription
                    });
                }
            });
        }

        function handle_payment_that_requires_customer_action({ subscription }) {
            // If it's a first payment attempt, the payment intent is on the subscription latest invoice.
            // If it's a retry, the payment intent will be on the invoice itself.
            if (subscription.latest_invoice.payment_intent) {
                const { id, client_secret, status } = subscription.latest_invoice.payment_intent;
                if (status === 'requires_action' || status === 'requires_payment_method') {
                    return stripe.confirmCardPayment(client_secret, {
                        payment_method: id,
                    }).then((result) => {
                        console.log(result);
                        if (result.error) {
                            // Start code flow to handle updating the payment details.
                            // Display error message in your UI.
                            // The card was declined (i.e. insufficient funds, card has expired, etc).
                            displayError(result.error);
                            throw result;
                        } else {
                            if (result.paymentIntent.status === 'succeeded') {
                                // Show a success message to your customer.
                                set_subscription('Stripe', subscription.id);
                            }
                        }
                    }).catch((error) => {
                        displayError(error);
                    });
                } else {
                    // No customer action needed.
                    set_subscription('Stripe', subscription.id);
                }
            } else {
                set_subscription('Stripe', subscription.id);
            }
        }

        // --- Payment System
        function createPaymentSubscription({
                                               period,
                                               paymentMethod,
                                               paymentMethodId = null
        }) {
            var result;

            $.ajax({
                url: '{{ route('subscription.create') }}',
                type: 'POST',
                data: {
                    period: period,
                    paymentMethod: paymentMethod,
                    paymentMethodId: paymentMethodId,
                    _token: '{{ csrf_token() }}'
                },
                async: false,
                success: function(data) {
                    result = data;
                }
            });

            return result;
        }

        function set_subscription(paymentMethod, paymentSubscriptionId) {
            $.ajax({
                url: '{{ route('subscription.set') }}',
                type: 'POST',
                data: {
                    payment_method: paymentMethod,
                    payment_subscription_id: paymentSubscriptionId,
                    plan: '{{ (isset($subscribeToPlan) ? $subscribeToPlan->slug : null) }}',
                    _token: '{{ csrf_token() }}'
                },
                success: function (data) {
                    if (data.success && data.content.redirect) {
                        document.location.href = data.content.redirect;
                    }
                }
            });
        }

        // --- Actions on Billing page
        var $form = $('.container'),
            $stripePayment = $form.find('#credit-card'),
            $paypalPayment = $form.find('#paypal-payment'),
            $subscribeButton = $form.find('#subscribe-button');

        $form.on('click', '[name="method"]', function () {
            var method = $(this).val();

            switch (method) {
                case 'card':
                    $paypalPayment.addClass('d-none');
                    $stripePayment.removeClass('d-none');
                    $subscribeButton.removeClass('d-none');
                    break;

                case 'paypal':
                    $stripePayment.addClass('d-none');
                    $paypalPayment.removeClass('d-none');
                    $subscribeButton.addClass('d-none');
                    break;
            }
        });

    </script>
@endsection