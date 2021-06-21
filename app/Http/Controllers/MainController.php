<?php

namespace App\Http\Controllers;

use App\Models\Subscription\Setting;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index()
    {
        return view('home');
    }

    public function prices()
    {
        $data = [
            'premium_trial_days' => Setting::getValueByKey('premium_trial_days'),
            'premium_month' => Setting::getValueByKey('premium_month'),
            'premium_year' => Setting::getValueByKey('premium_year'),
        ];

        return view('prices', $data);
    }

    public function about()
    {
        return view('about');
    }
}
