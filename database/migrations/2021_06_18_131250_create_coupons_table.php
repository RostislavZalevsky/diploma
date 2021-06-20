<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('code')->unique();

            $table->enum('off', ['amount', 'percent'])->default('percent');
            $table->float('discount');
            $table->enum('duration', ['once', 'forever', 'repeating'])->default('once');
            $table->integer('repeat_count')->default(0); // if the duration is repeating, it can set the number of times to repeat
            $table->dateTime('expired_at');

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
        Schema::dropIfExists('coupons');
    }
}
