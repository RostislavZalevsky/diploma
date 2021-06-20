<?php

namespace Database\Seeders;

use App\Models\Payment\PayPal;
use App\Models\Payment\Stripe;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $stripe = new Stripe();
        $stripe->initPayment();
        $paypal = new PayPal();
        $paypal->initPayment();
    }
}
