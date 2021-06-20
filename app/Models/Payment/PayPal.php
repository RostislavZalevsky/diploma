<?php

namespace App\Models\Payment;

use App\Models\Subscription\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPal extends Model
{
    public $provider;

    public function __construct()
    {
        $this->provider = new PayPalClient();
        $this->provider->setApiCredentials(config('paypal'));
        $this->provider->setAccessToken($this->provider->getAccessToken());
    }

    public static function getApiKey()
    {
        return config('paypal.client_id');
    }

    public static function getApiSecretKey()
    {
        return config('paypal.secret');
    }

    public static function getWebhookKey()
    {
        return config('paypal.webhook');
    }

    public function initPayment()
    {
        $created_product = $this->provider->createProduct(
            [
                'name' => config('app.name'),
                'type' => 'SERVICE',
                'category' => 'EXERCISE_AND_FITNESS',
            ],
            self::getApiKey());

        PaymentMethod::updateOrCreate(
            ['name' => 'PayPal',],
            ['product_id' => $created_product['id']]
        );
    }

    function getProductId()
    {
        return PaymentMethod::where('name', '=', 'PayPal')
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

        $plan = [
            "product_id" => $this->getProductId(),
            "name" => config('app.name') . ' $' . $data['amount'] . ' USD per ' . $data['interval_unit'],
            "description" => json_encode($metadata),
            "status" => "ACTIVE",
            "billing_cycles" => [],
            "payment_preferences" => [
                "auto_bill_outstanding" => true
            ]
        ];

        if (isset($data['trial_interval_unit']) && isset($data['trial_interval_count']))
        {
            $sequence = count($plan['billing_cycles']) + 1;

            $plan['billing_cycles'][] = [
                "frequency" => [
                    "interval_unit" => strtoupper($data['trial_interval_unit']),
                    "interval_count" => $data['trial_interval_count']
                ],
                "tenure_type" => "TRIAL",
                "sequence" => $sequence,
                "total_cycles" => 1,
                "pricing_scheme" => [
                    "fixed_price" => [
                        "value" => 0,
                        "currency_code" => strtoupper($data['currency'])
                    ]
                ]
            ];
        }

        if (isset($data['coupon']))
        {
            $discount =
                ($data['coupon']->off == 'percent')
                    ? $data['amount'] * ($data['coupon']->discount / 100)
                    : $data['coupon']->discount;

            $discounted = $data['amount'] - $discount;
            $discounted = $discounted < 0 ? 0 : $discounted;

            switch ($data['coupon']->duration)
            {
                case 'once':
                case 'repeating': {
                    $sequence = count($plan['billing_cycles']) + 1;

                    $plan['billing_cycles'][] = [
                        "frequency" => [
                            "interval_unit" => strtoupper($data['interval_unit']),
                            "interval_count" => $data['interval_count']
                        ],
                        "tenure_type" => "TRIAL",
                        "sequence" => $sequence,
                        "total_cycles" => ($data['coupon']->duration == 'once' ? 1 : $data['coupon']->repeat_count),
                        "pricing_scheme" => [
                            "fixed_price" => [
                                "value" => $discounted,
                                "currency_code" => strtoupper($data['currency'])
                            ]
                        ]
                    ];
                    break;
                }
                case 'forever':
                    $data['amount'] = $discounted;
                    break;
            }
        }

        $sequence = count($plan['billing_cycles']) + 1;
        $plan['billing_cycles'][] = [
            "frequency" => [
                "interval_unit" => strtoupper($data['interval_unit']),
                "interval_count" => $data['interval_count']
            ],
            "tenure_type" => "REGULAR",
            "sequence" => $sequence,
            "total_cycles" => 0,
            "pricing_scheme" => [
                "fixed_price" => [
                    "value" => $data['amount'],
                    "currency_code" => strtoupper($data['currency'])
                ]
            ]
        ];

        return $this->provider->createPlan($plan);
    }

    public function getPlan($id)
    {
        return $this->provider->showPlanDetails($id);
    }


    public function getSubscription($id)
    {
        return $this->provider->showSubscriptionDetails($id);
    }

    public function cancelSubscription($id)
    {
        $this->provider->cancelSubscription($id, null);

        $subscription = $this->getSubscription($id);
        if ($subscription['status'] == 'CANCELLED') {
            $this->provider->deactivatePlan($subscription['plan_id']);
        }

        return $subscription['status'];
    }

    public function list_invoices($subscription_id, $start_time = null)
    {
        if (!isset($start_time)) {
            $start_time = date('Y-m-d\TH:i:s\Z', strtotime('-1 year'));
        }

        return $this->provider->listSubscriptionTransactions($subscription_id, $start_time);
    }
}
