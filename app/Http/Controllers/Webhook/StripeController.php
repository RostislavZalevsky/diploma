<?php

namespace App\Http\Controllers\Webhook;

use App\Models\Payment\Stripe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

class StripeController extends CashierController
{
    public function __construct()
    {
        $this->middleware(VerifyWebhookSignature::class);
    }

    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $json = json_encode($payload, JSON_PRETTY_PRINT);

        $stripe = new Stripe();

        try {
            switch ($payload['data']['object']['object'])
            {
                case 'invoice':
                    $invoice = $payload['data']['object'];
                    $subscription = $stripe->getSubscription($invoice['subscription']);
                    $metadata = $subscription['plan']['metadata'];

                    if (! isset($metadata['user_id']) || ! ($user = User::query()->find($metadata['user_id'], ['id']))) return exit(1);

                    if (! ($subscriptionDB = $user->subscriptions()->where('payment_subscription_id', '=', $subscription['id'])->first()))
                    {
                        $subscriptionDB = $user->setSubscription(
                            'Stripe',
                            $subscription['id'],
                            $metadata['amount'],
                            $metadata['period'],
                            $subscription['status']);
                    }

                    $subscriptionDB->setTransaction(
                        $invoice['id'],
                        $invoice['amount_paid'] / 100,
                        $invoice['status'],
                        $invoice['status_transitions']['paid_at'],
                        isset($invoice->charge->receipt_url) ? $invoice->charge->receipt_url : null);

                    return exit(1);
                case 'subscription':
                    $subscription = $payload['data']['object'];
                    $metadata = $subscription['plan']['metadata'];

                    if (! isset($metadata['user_id']) || ! ($user = User::query()->find($metadata['user_id'], ['id']))) return exit(1);

                    if (! ($subscriptionDB = $user->subscriptions()->where('payment_subscription_id', '=', $subscription['id'])->first()))
                    {
                        $subscriptionDB = $user->setSubscription(
                            'Stripe',
                            $subscription['id'],
                            $metadata['amount'],
                            $metadata['period'],
                            $subscription['status']);
                    }
                    else {
                        $subscriptionDB->setStatus($subscription['status']);
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
                    return exit(1);
            }

            Mail::raw($json, function($message) use ($payload) {
                $message->from('z.rostislav11@gmail.com', 'BULKTRA');
                $message->to('z.rostislav11@gmail.com');
                $message->subject(config('app.env') . ' Webhook Stripe ' . $payload['type']);
            });
        } catch (\Exception $exception) {
            $json = json_encode($payload, JSON_PRETTY_PRINT);
            Mail::raw($json . '

            ' . $exception->getMessage() . ' ' . $exception->getLine() . ' ' . $exception->getCode(), function($message) use ($payload) {
                $message->from('z.rostislav11@gmail.com', 'BULKTRA');
                $message->to('z.rostislav11@gmail.com');
                $message->subject(config('app.env') . ' Webhook Stripe ERROR ' . ' ' . $payload['type']);
            });
        }
    }
}
