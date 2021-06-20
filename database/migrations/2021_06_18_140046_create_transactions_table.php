<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('subscription_id')->index();
            $table->string('payment_transaction_id')->unique();
            $table->double('amount');
            $table->dateTime('paid_at');
            $table->enum('status', ['succeeded', 'failed']);
            $table->string('payment_status')->nullable();
            $table->string('receipt')->nullable();
            $table->foreign('subscription_id')->references('id')->on('subscriptions');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
