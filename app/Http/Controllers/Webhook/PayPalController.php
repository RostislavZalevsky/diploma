<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment\PayPal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PayPalController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $json = json_encode($payload, JSON_PRETTY_PRINT);

        $paypal = new PayPal();
        $resultVerification = $paypal->provider->verifyWebHook([
            'auth_algo' => $request->header('paypal-auth-algo'),
            'cert_url' => $request->header('paypal-cert-url'),
            'transmission_id' => $request->header('paypal-transmission-id'),
            'transmission_sig' => $request->header('paypal-transmission-sig'),
            'transmission_time' => $request->header('paypal-transmission-time'),
            'webhook_id' => PayPal::getWebhookKey(),
            'webhook_event' => $payload,
        ]);

        if (isset($resultVerification['verification_status']) && $resultVerification['verification_status'] !== 'SUCCESS') {
            http_response_code(404);
            return exit(1);
        }

        try {
            switch ($payload['resource_type']) {
                case 'sale':
                    $invoice = $payload['resource'];

                    $subscription = $paypal->getSubscription($invoice['billing_agreement_id']);
                    $plan = $paypal->getPlan($subscription['plan_id']);
                    $metadata = json_decode($plan['description'], true);

                    if (! isset($metadata['user_id']) || ! ($user = User::query()->find($metadata['user_id'], ['id']))) return exit(1);

                    if (! ($subscriptionDB = $user->subscriptions()->where('payment_subscription_id', '=', $subscription['id'])->first()))
                    {
                        $subscriptionDB = $user->setSubscription(
                            'Stripe',
                            $subscription['id'],
                            $metadata['amount'],
                            $metadata['period'],
                            $subscription['status'],
                            $subscription[(isset($subscription['status_update_time']) ? 'status_update_time' : 'create_time')]
                        );
                    }

                    $subscriptionDB->setTransaction(
                        $invoice['id'],
                        $invoice['amount']['total'],
                        $invoice['state'],
                        $invoice['update_time']);

                    http_response_code(200);
                    return exit(1);
                case 'subscription':
                    $subscription = $payload['resource'];
                    $plan = $paypal->getPlan($subscription['plan_id']);
                    $metadata = json_decode($plan['description'], true);

                    if (! isset($metadata['user_id']) || ! ($user = User::query()->find($metadata['user_id'], ['id']))) return exit(1);

                    if (! ($subscriptionDB = $user->subscriptions()->where('payment_subscription_id', '=', $subscription['id'])->first()))
                    {
                        $subscriptionDB = $user->setSubscription(
                            'PayPal',
                            $subscription['id'],
                            $metadata['amount'],
                            $metadata['period'],
                            $subscription['status'],
                            $subscription[(isset($subscription['status_update_time']) ? 'status_update_time' : 'create_time')]
                        );
                    }
                    else {
                        $subscriptionDB->setStatus($subscription['status'], $subscription[(isset($subscription['status_update_time']) ? 'status_update_time' : 'create_time')]);
                    }

                    if (isset($metadata['trial']) && !empty($metadata['trial']) && $metadata['trial'] > 0 && !$user->trial()) {
                        $user->trial(
                            date('Y-m-d', strtotime('+' . $metadata['trial'] . ' day'))
                        );
                    }

                    if (isset($metadata['coupon']) && !empty($metadata['coupon']) && $metadata['coupon'] > 0 && !$user->coupons()->find($metadata['coupon'])) {
                        $user->coupons()->attach(
                            (int)$metadata['coupon'], [
                            'subscription_id' => $subscription['id']
                        ]);
                    }

                    http_response_code(200);
                    return exit(1);
            }

            Mail::raw(($json . '

            ' . (isset($resultVerification["message"]) ? $resultVerification["message"] : json_encode($resultVerification))),
                function($message) use ($payload, $resultVerification) {
                    $message->from('z.rostislav11@gmail.com', 'BULKTRA');
                    $message->to('z.rostislav11@gmail.com');
                    $message->subject(config('app.env') . ' Webhook PayPal ' . (isset($resultVerification['verification_status']) ? ' ' . $resultVerification['verification_status'] : '') . ' ' . $payload['event_type']);
                }
            );
        } catch (\Exception $exception) {
            Mail::raw(($json . '

            ' . $exception->getMessage() . ' ' . $exception->getLine() . ' ' . $exception->getCode()),
                function($message) use ($payload, $resultVerification) {
                    $message->from('z.rostislav11@gmail.com', 'BULKTRA');
                    $message->to('z.rostislav11@gmail.com');
                    $message->subject(config('app.env') . ' Webhook PayPal ERROR ' . (isset($resultVerification['verification_status']) ? ' ' . $resultVerification['verification_status'] : '') . ' ' . $payload['event_type']);
                }
            );

            http_response_code(503);
            return exit(1);
        }

        if (isset($resultVerification["message"]))
        {
            http_response_code(503);
            return exit(1);
        }

        http_response_code(200);
        return exit(1);
    }
}
