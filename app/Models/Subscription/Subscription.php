<?php

namespace App\Models\Subscription;

use App\Models\Payment\PayPal;
use App\Models\Payment\Stripe;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_method_id',
        'payment_subscription_id',
        'amount',
        'interval',
        'subscription_status_id',
    ];

    protected static function booted()
    {
        parent::booted(); // TODO: Change the autogenerated stub
        static::created(function (Subscription $model) {
            $model->user()->first()->sendWelcomeEmailNotification();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(SubscriptionStatus::class);
    }

    public function setStatus($paymentStatus, $statusUpdatedAt = null)
    {
        $subscriptionStatus = $this->subscriptionStatus()->first(['payment_status', 'created_at']);

        if ($this->subscriptionStatus()->exists()
            && $subscriptionStatus->payment_status == $paymentStatus
            && (!isset($statusUpdatedAt)
                || $this->statuses()->whereDate('created_at', '=', Carbon::parse($statusUpdatedAt))
                    ->where('payment_status', '=', $paymentStatus)->exists()
                || $this->statuses()->whereDate('created_at', '<', Carbon::parse($statusUpdatedAt))
                    ->first('payment_status')->payment_status == $paymentStatus)
        ) return;

        $statusPayment = [];
        switch ($this->paymentMethod()->first('name')->name)
        {
            // The status of the subscription.
            // The possible values are:
            case 'Stripe':
                // active
                // past_due
                // unpaid
                // canceled
                // incomplete
                // incomplete_expired
                // trialing
                $statusPayment['active'] = ['trialing', 'active'];
                $statusPayment['cancelled'] = ['canceled'];
                break;
            case 'PayPal':
                // APPROVAL_PENDING. The subscription is created but not yet approved by the buyer.
                // APPROVED. The buyer has approved the subscription.
                // ACTIVE. The subscription is active.
                // SUSPENDED. The subscription is suspended.
                // CANCELLED. The subscription is cancelled.
                // EXPIRED. The subscription is expired.
                $statusPayment['active'] = ['active'];
                $statusPayment['cancelled'] = ['cancelled'];
                break;
        }

        $attributes = [
            'payment_status' => $paymentStatus,
            'status' => (in_array(mb_strtolower($paymentStatus), $statusPayment['active']) ? 'active' : 'inactive'),
        ];

        $s = $this->statuses()->create($attributes);

        if (isset($statusUpdatedAt)) {
            $statusUpdatedAt = Carbon::parse($statusUpdatedAt);
            $s->created_at = $statusUpdatedAt;
            $s->save(['timestamps' => false]);

            if ($statusUpdatedAt->lessThan($subscriptionStatus->created_at))
                return;
        }

        $this->update(['subscription_status_id' => $s->id]);
    }

    public function subscriptionStatus(): BelongsTo
    {
        return $this->belongsTo(SubscriptionStatus::class)->latest();
    }

    public function status()
    {
        $status = $this->subscriptionStatus();

        return $status->exists()
            ? $status->first('status')->status
            : 'inactive';
    }

    public function isActive(): bool
    {
        if ($this->status() == 'active')
            return true;

        if ($this->paymentMethod()->first()->name == 'Stripe'
            || ! ($transaction = $this->transactions()->where([['status', '=', 'succeeded'], ['amount', '>', '0']])->latest('paid_at')->first()))
            return false;

        $paid_at = Carbon::parse($transaction->paid_at);
        $paid_at->{($this->interval == 'month' ? 'addMonth' : 'addYear')}();

        return $paid_at->greaterThan(Carbon::today());
    }

    public function isCancelled(): bool // TODO: missed webhook on localhost after 90 days
    {
        return !$this->isActive() &&
            in_array($this->subscriptionStatus()->first('payment_status')->payment_status, ['canceled', 'CANCELLED']);
    }

    public function canCancel(): bool
    {
        return !$this->isCancelled();
    }

    public function setTransaction($transaction_id, $amount, $paymentStatus, $paid_at, $receipt = null): Model
    {
        $attribute = [
            'amount' => $amount,
            'payment_status' => $paymentStatus,
            'paid_at' => \Carbon\Carbon::parse($paid_at),
        ];

        $statusSucceeded = [];
        switch ($this->paymentMethod()->first('name')->name)
        {
            // The status of the transaction.
            // The possible values are:
            case 'Stripe':
                // draft	        Starting status for all invoices—you can still edit the invoice at this point.  Finalize it to open, or delete it if it’s a one-off invoice.
                // open             The invoice has been finalized, and is awaiting payment from the customer—you can no longer edit it.    Send, void, mark uncollectible, or pay the invoice.
                // paid	            This invoice was paid.  –
                // void	            This invoice was a mistake, and should be canceled. –
                // uncollectible	It’s unlikely that this invoice will be paid—you should treat it as “bad debt” in reports.  Void or pay the invoice.
                $statusSucceeded = ['paid'];
                break;
            case 'PayPal':
                // The status of the captured payment.
                // Completed. The transaction is complete and the money has been transferred to the payee.
                // Partially_Refunded. A part of the transaction amount has been refunded to the payer.
                // Pending. The transaction is pending settlement.
                // Refunded. The transaction amount has been refunded to the payer.
                // Denied. The transaction has been denied.
                $statusSucceeded = ['completed'];
                break;
        }

        $attribute['status'] = in_array(mb_strtolower($paymentStatus), $statusSucceeded) ? 'succeeded' : 'failed';

        if (isset($receipt)) $attribute['receipt'] = $receipt;

        return $this->transactions()->updateOrCreate(
            ['payment_transaction_id' => $transaction_id],
            $attribute
        );
    }

    public function cancelSubscription()
    {
        switch ($this->paymentMethod()->first('name')->name)
        {
            case 'PayPal':
                $paypal = new PayPal();
                $status = $paypal->cancelSubscription($this->payment_subscription_id);
                break;
            case 'Stripe':
                $stripe = new Stripe();
                $status = 'canceled'; //$stripe->cancelSubscription($this->payment_subscription_id);
                $this->user()->first()->deletePaymentMethods();
                $this->user()->first()->updateDefaultPaymentMethodFromStripe();
                break;
        }

        $this->setStatus($status);
    }
}
