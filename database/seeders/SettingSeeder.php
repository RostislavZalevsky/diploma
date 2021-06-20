<?php

namespace Database\Seeders;

use App\Models\Subscription\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Setting::create([
            'key' => 'premium_trial_days',
            'value' => 7
        ]);

        Setting::create([
            'key' => 'premium_month',
            'value' => 15
        ]);

        Setting::create([
            'key' => 'premium_year',
            'value' => 120
        ]);
    }
}
