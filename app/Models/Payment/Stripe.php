<?php

namespace App\Models\Payment;

use App\Models\Subscription\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Stripe extends Model
{
    public $stripe;

    public function __construct(array $attributes = [])
    {
        $this->stripe = new \Stripe\StripeClient(
            self::getApiSecretKey()
        );
    }

    public static function getApiKey()
    {
        return config('stripe.key');
    }

    public static function getApiSecretKey()
    {
        return config('stripe.secret');
    }

    public static function getWebhookKey()
    {
        return config('stripe.webhook');
    }


    public function initPayment()
    {
        $product = $this->stripe->products->create([
            'name' => config('app.name'),
        ]);

        PaymentMethod::updateOrCreate(
            ['name' => 'Stripe',],
            ['product_id' => $product['id']]
        );
    }

    public function getProductId()
    {
        return PaymentMethod::where('name', '=', 'Stripe')
            ->select('product_id')
            ->first()->product_id;
    }


    public function createPlan($data)
    {
        $metadata = [
            'user_id' => Auth::user()->id,
            'amount' => $data['amount'],
            'period' => $data['interval_unit'],
            'trial' => isset($data['trial_interval_count']) ? $data['trial_interval_count'] : null,
            'coupon' => isset($data['coupon']) ? $data['coupon']->id : null,
        ];

        $price = $this->stripe->prices->create([
            'product' => $this->getProductId(),
            'unit_amount' => $data['amount'] * 100,
            'currency' => strtolower($data['currency']),
            'recurring' => [
                'interval' => strtolower($data['interval_unit']),
                'interval_count' => $data['interval_count'],
            ],
            'metadata' => $metadata,
        ]);

        return $price;
    }

    public function getPlan($id)
    {
        try {
            return $this->stripe->prices->retrieve($id);
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getSubscription($id)
    {
        return $this->stripe->subscriptions->retrieve($id);
    }

    public function cancelSubscription($id)
    {
        $subscription = $this->getSubscription($id);

        switch ($subscription['status'])
        {
            case 'canceled':
                if ($this->getPlan($subscription['plan']['id']))
                {
                    $this->stripe->plans->delete($subscription['plan']['id']);
                }
                break;
            default:
                $subscription = $this->stripe->subscriptions->cancel($id);
                if ($subscription['status'] == 'canceled') {
                    $this->stripe->plans->delete($subscription['plan']['id']);
                }
                break;
            case 'active':
                $subscription = $this->stripe->subscriptions->update($id, [
                    'cancel_at_period_end' => true,
                ]);
                break;
        }

        return $subscription['status'];
    }


    public function create_coupon($plan)
    {
        $params = [
            'currency' => $plan['currency'],
            'duration' => $plan['coupon']->duration,
            $plan['coupon']->off . '_off' =>
                ($plan['coupon']->off == 'amount' ? 100 : 1) * $plan['coupon']->discount
        ];

        if ($plan['coupon']->duration == 'repeating')
        {
            $params['duration_in_months'] =
                ($plan['interval_unit'] == 'year' ? 12 : 1) * $plan['coupon']->repeat_count;
        }

        return $this->stripe->coupons->create($params);
    }

    public function create_subscription($payment_method_id, $customer_id, $price_id, $coupon = null)
    {
        $stripe = $this->stripe;
        $user = Auth::user();
        try {
            $payment_method = $stripe->paymentMethods->retrieve(
                $payment_method_id
            );

            $user->addPaymentMethod($payment_method);
        } catch (Exception $e) {
            return [
                'success' => false,
                'content' => [
                    'session_id' => $e->getMessage(),
                ],
                'message' => $e->getMessage(),
            ];
        }

        // Set the default payment method on the customer
        $user->updateDefaultPaymentMethod($payment_method);
        $user->updateDefaultPaymentMethodFromStripe(); // set to db

        // Create the subscription
        $stripe_subscription = [
            'customer' => $customer_id,
            'items' => [
                [
                    'price' => $price_id,
                ],
            ],
            'expand' => ['latest_invoice.payment_intent', 'pending_setup_intent'],
        ];

        if (isset($coupon))
        {
            $stripe_subscription['coupon'] = $coupon;
        }

//        if (!Auth::user()->trial())
//        {
//            $trial = strtotime(
//                '+' . Setting::getValueByKey('premium_trial_days') . ' day');
//
//            $stripe_subscription['trial_end'] = $trial;
//        }

        $subscription = $stripe->subscriptions->create($stripe_subscription);

        return [
            'success' => true,
            'content' => [
                'subscription' => $subscription,
            ],
            'message' => 'OK',
        ];
    }

    public function list_invoices($subscription_id)
    {
        return $this->stripe->invoices->all([
            'subscription' => $subscription_id,
            'limit' => 100,
            'expand' => ['data.charge', 'data.payment_intent', 'data.default_payment_method', 'data.default_source'],
        ]);
    }

    public function get_payment_method($id)
    {
        return $this->stripe->paymentMethods->retrieve(
            $id,[]
        );
    }

    public function retrieve_upcoming($customer_id, $subscription_id)
    {
        return $this->stripe->invoices->upcoming([
            'customer' => $customer_id,
            'subscription' => $subscription_id,
        ]);
    }
}
