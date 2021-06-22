<?php

namespace App\Http\Controllers;

use App\Models\Payment\PayPal;
use App\Models\Payment\Stripe;
use App\Models\Subscription\PaymentMethod;
use App\Models\Subscription\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use function Symfony\Component\String\u;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $data = ['user' => $user];

        if ($user->isSubscribed() && $user->subscription()->isActive())
        {
            $data['subscriber'] = $user->getSubscriptionDetails()['subscriber'];
            $data['subscription'] = $user->subscription();
            $data['canCancel'] = $user->subscription()->canCancel();
            $data['transactions'] = $user->transactions();

            return view('subscribed', $data);
        }
        else
        {
            $data += [
                'paypal_client_id' => PayPal::getApiKey(),
                'stripe_client_id' => Stripe::getApiKey(),
                'premium_trial_days' => Setting::getValueByKey('premium_trial_days'),
                'premium_month' => Setting::getValueByKey('premium_month'),
                'premium_year' => Setting::getValueByKey('premium_year'),
            ];

            return view('subscription', $data);
        }
    }

    public function createPaymentSubscription(Request $request)
    {
        $data = $request->only(['period', 'paymentMethod', 'paymentMethodId']);

        $price = Setting::getValueByKey('premium_' . $data['period']);
        $paymentMethod = PaymentMethod::getByName($data['paymentMethod']);

        if (!$price && !$paymentMethod) return;

        $plan = [
            'amount' => $price,
            'currency' => 'usd',
            'interval_unit' => $data['period'],
            'interval_count' => 1,
        ];

        switch ($paymentMethod->name)
        {
            case 'PayPal':
                $payment = new PayPal();
                return $payment->createPlan($plan)['id'];
            case 'Stripe':
                $payment = new Stripe();
                $price_id = $payment->createPlan($plan)['id'];

                $result = $payment->create_subscription(
                    $request['paymentMethodId'],
                    Auth::user()->createOrGetStripeCustomer()->id,
                    $price_id,
                    null);

                return response()->json($result);
        }
    }

    public function setSubscription(Request $request)
    {
        if (!Auth::user()->isSubscribed() || !Auth::user()->subscription()->isActive())
        {
            switch ($request['payment_method'])
            {
                case 'PayPal':
                    $paypal = new PayPal();
                    $retrieve = $paypal->getSubscription($request['payment_subscription_id']);
                    $plan = $paypal->getPlan($retrieve['plan_id']);

                    $metadata = json_decode($plan['description'], true);
                    $paymentStatus = $retrieve['status'];
                    break;
                case 'Stripe':
                    $stripe = new Stripe();
                    $retrieve = $stripe->getSubscription($request['payment_subscription_id']);

                    $metadata = $retrieve['plan']['metadata'];
                    $paymentStatus = $retrieve['status'];
                    break;
            }

            $subscription = Auth::user()
                ->setSubscription(
                    $request['payment_method'], $request['payment_subscription_id'],
                    $metadata['amount'], $metadata['period'],
                    $paymentStatus);

            $json = [
                'success' => true,
                'content' => [
                    'id' => $subscription->id,
                    'redirect' => route('subscription.index')
                ],
                'message' => 'OK',
            ];
        }
        else
        {
            $json = [
                'success' => false,
                'content' => [
                    'error' => 'user_has_subscription',
                ],
                'message' => 'user_has_subscription',
            ];
        }

        return response()->json($json);
    }

    public function cancel()
    {
        Auth::user()->subscription()->cancelSubscription();

        return redirect()->back()->with('success', 'Subscription cancelled successfully.');
    }
}
