<?php

namespace App\Models;

use App\Models\Payment\PayPal;
use App\Models\Payment\Stripe;
use App\Models\Subscription\Coupon;
use App\Models\Subscription\PaymentMethod;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\Transaction;
use App\Notifications\Account\EmailVerificationNotification;
use App\Notifications\Account\PasswordResetNotification;
use App\Notifications\Account\WelcomeEmailNotification;
use App\Traits\UsesUuid;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, Billable, UsesUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function routeNotificationForMail($notification)
    {
        return [$this->email => $this->name];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscription()
    {
        return $this->subscriptions()
            ->join('subscription_statuses', 'subscription_statuses.id', '=', 'subscriptions.subscription_status_id')
            ->orderBy('subscription_statuses.status')
            ->latest('subscriptions.created_at')
            ->first('subscriptions.*');
    }

    public function isSubscribed(): bool
    {
        return (bool)$this->subscription();
    }

    public function getSubscriptionDetails()
    {
        if (!$this->isSubscribed()) return null;

        $subscriptionDB = $this->subscription();
        switch ($subscriptionDB->paymentMethod->name)
        {
            case 'PayPal':
                $paypal = new PayPal();
                $retrieve = $paypal->getSubscription($subscriptionDB->payment_subscription_id);
                $plan = $paypal->getPlan($retrieve['plan_id']);

                array_multisort(array_column($plan['billing_cycles'], 'sequence'), SORT_ASC, $plan['billing_cycles']);
                array_multisort(array_column($retrieve['billing_info']['cycle_executions'], 'sequence'), SORT_ASC, $retrieve['billing_info']['cycle_executions']);

                foreach ($retrieve['billing_info']['cycle_executions'] as $key => $cycle_execution)
                {
                    if ($cycle_execution['total_cycles'] != 0
                        && $cycle_execution['total_cycles'] == $cycle_execution['cycles_completed'])
                        continue;

                    $price = $plan['billing_cycles'][$key]['pricing_scheme']['fixed_price']['value'];
                    break;
                }

                $subscription['subscriber'] = [
                    'name' => $retrieve['subscriber']['name']['given_name'] . ' ' . $retrieve['subscriber']['name']['surname'],
                    'email' => $retrieve['subscriber']['email_address'],
                    'price' => number_format($price, 2),
                    'next_payment_date' => isset($retrieve['billing_info']['next_billing_time'])
                        ? convertFormatDate($retrieve['billing_info']['next_billing_time'])
                        : null
                ];
                break;
            case 'Stripe':
                $subscription['subscriber'] = [];

                $stripe = new Stripe();

                if (isset($this->createOrGetStripeCustomer()->invoice_settings->default_payment_method))
                {
                    $paymentMethod = $stripe->get_payment_method($this->createOrGetStripeCustomer()->invoice_settings->default_payment_method);;
                    $subscription['subscriber']['name'] = $paymentMethod->billing_details->name;
                    $subscription['subscriber']['card'] = [
                        'funding' => $paymentMethod->card->funding,
                        'brand' => $paymentMethod->card->brand,
                        'last4' => $paymentMethod->card->last4,
                        'exp_month' => sprintf("%'02d", $paymentMethod->card->exp_month),
                        'exp_year' => substr($paymentMethod->card->exp_year, -2),
                    ];
                }

                $upcoming = $stripe->retrieve_upcoming($this->createOrGetStripeCustomer()->id, $subscriptionDB->payment_subscription_id);
                $subscription['subscriber']['price'] = number_format($upcoming->total / 100, 2);
                $subscription['subscriber']['next_payment_date'] = convertFormatDate($upcoming->period_end, false);
                break;
        }

        return $subscription;
    }

    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, Subscription::class)->latest('paid_at');
    }

//    public function coupons()
//    {
//        return $this->belongsToMany(Coupon::class)->withPivot(['subscription_id', 'created_at']);
//    }

    public function setSubscription($payment_method, $payment_subscription_id, $amount, $period, $status = null, $statusUpdatedAt = null)
    {
        $subscription = $this->subscriptions()->updateOrCreate(
            [
                'payment_method_id' => PaymentMethod::getIdByName($payment_method),
                'payment_subscription_id' => $payment_subscription_id,
            ],
            [
                'amount' => $amount,
                'interval' => $period,
            ]
        );

        if (isset($status)) $subscription->setStatus($status, $statusUpdatedAt);

        return $subscription;
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new EmailVerificationNotification());
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetNotification($token));
    }

    public function sendWelcomeEmailNotification()
    {
        $this->notify(new WelcomeEmailNotification);
    }
}
