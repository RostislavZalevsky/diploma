<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Payment\PayPal;
use App\Models\Payment\Stripe;
use App\Models\Subscription\Coupon;
use App\Models\Subscription\PaymentMethod;
//use App\Models\Subscription\Plan;
use App\Models\Subscription\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function createPaymentSubscription(Request $request)
    {
        $data = $request->only(['period', 'paymentMethod', 'paymentMethodId', 'coupon']);

        $period = $data['period'] == 'monthly' ? 'month' : 'year';
        $price = Setting::getValueByKey('premium_' . $period);
        $paymentMethod = PaymentMethod::getByName($data['paymentMethod']);

        if (!$price && !$paymentMethod) return;

        $plan = [
            'amount' => $price,
            'currency' => 'usd',
            'interval_unit' => $period,
            'interval_count' => 1,
        ];

        if (Coupon::query()->where('code', $data['coupon'])->exists()
            && !Auth::user()->coupons()->where('code', $data['coupon'])->exists()
            && !Carbon::parse(Coupon::query()->where('code', $request['coupon'])->first('expired_at')->expired_at)->lessThan(Carbon::today())
        )
        {
            $plan['coupon'] = Coupon::query()->where('code', $data['coupon'])->first();
        }

        if (!Auth::user()->trial()) {
            $plan += [
                'trial_interval_unit' => 'day',
                'trial_interval_count' => Setting::getValueByKey('premium_trial_days'),
            ];
        }

        switch ($paymentMethod->name)
        {
            case 'PayPal':
                $payment = new PayPal();
                return $payment->createPlan($plan)['id'];
            case 'Stripe':
                $payment = new Stripe();
                $price_id = $payment->createPlan($plan)['id'];

                if (isset($plan['coupon']))
                {
                    $coupon = $payment->create_coupon($plan)['id'];
                }

                $result = $payment->create_subscription(
                    $request['paymentMethodId'],
                    Auth::user()->createOrGetStripeCustomer()->id,
                    $price_id,
                    (isset($coupon) ? $coupon : null));

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

//            if (isset($request['plan']) && Plan::where('slug', $request['plan'])->exists()) {
//                $plan = Plan::where('slug', $request['plan'])->first();
//                $this->subscribe($plan->company, $plan);
//            }

//            if (config('app.env') === 'local')
//            {
            if (isset($metadata['trial']) && !empty($metadata['trial']) && $metadata['trial'] > 0) {
                Auth::user()->trial(
                    date('Y-m-d', strtotime('+' . $metadata['trial'] . ' day'))
                );
            }

            if (isset($metadata['coupon']) && !empty($metadata['coupon']) && $metadata['coupon'] > 0) {
                Auth::user()->coupons()->attach(
                    (int)$metadata['coupon'], [
                    'subscription_id' => $subscription['id']
                ]);
            }
//            }

            $json = [
                'success' => true,
                'content' => [
                    'id' => $subscription->id,
                    'redirect' => route('profile.schedule')
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

    public function getCoupon(Request $request)
    {
        if (!Coupon::query()->where('code', $request['coupon'])->exists()
            || Carbon::parse(Coupon::query()->where('code', $request['coupon'])->first('expired_at')->expired_at)->lessThan(Carbon::today()))
            return response()->json([
                'discount' => 0,
                'message' => 'Coupon is invalid',
                'type' => 'error',
            ]);

        if (Auth::user()->coupons()->where('code', $request['coupon'])->exists())
            return response()->json([
                'discount' => 0,
                'message' => 'Coupon has been used',
                'type' => 'error',
            ]);

        $period = $request['period'] == 'monthly' ? 'month' : 'year';
        $price = Setting::getValueByKey('premium_' . $period);
        $coupon = Coupon::query()->where('code', $request['coupon'])->first();
        $discount =
            ($coupon->off == 'percent')
                ? $price * ($coupon->discount / 100)
                : $coupon->discount;

        return response()->json([
            'discount' => $discount,
            'message' => 'Coupon applied successfully',
            'type' => 'success',
        ]);
    }

    public function getSubscriptionDetails(Request $request)
    {
        $details = '';
        if (!Auth::user()->trial()) {
            $details .= 'Free for ' . Setting::getValueByKey('premium_trial_days') . ' days\nThen ';
        }

        $interval = $request['period'] == 'monthly' ? 'month' : 'year';
        $price = Setting::getValueByKey('premium_' . $interval);

        if (!empty($request['coupon'])
            && Coupon::query()->where('code', $request['coupon'])->exists()
            && !Auth::user()->coupons()->where('code', $request['coupon'])->exists()
            && !Carbon::parse(Coupon::query()->where('code', $request['coupon'])->first('expired_at')->expired_at)->lessThan(Carbon::today())
        )
        {
            $coupon = Coupon::query()->where('code', $request['coupon'])->first();
            $discount = ($coupon->off == 'percent')
                ? $price * ($coupon->discount / 100)
                : $coupon->discount;

            switch ($coupon->duration)
            {
                case 'once':
                    $discounted = $price - $discount;
                    $discounted = $discounted < 0 ? 0 : $discounted;
                    $details .= ($discounted == 0 ? 'Free' : '$' . number_format($discounted, 2) . ' USD') . ' for one ' . $interval . '\nThen ';
                    break;
                case 'repeating':
                    $discounted = $price - $discount;
                    $discounted = $discounted < 0 ? 0 : $discounted;
                    $details .= ($discounted == 0 ? 'Free' : '$' . number_format($discounted, 2) . ' USD') . ' for each ' . $interval . ', for ' . $coupon->repeat_count . ' installments\nThen ';
                    break;
                case 'forever':
                    $price -= $discount;
                    $price = $price < 0 ? 0 : $price;
                    break;
            }
        }

        $details .= ($price == 0 ? 'Free' : '$' . number_format($price, 2) . ' USD') . ' for each ' . $interval;

        $details .= '\n\n(Renews until you cancel' . ($interval == 'month' ? ' after three months' : '') . ')';
        return $details;
    }
}
