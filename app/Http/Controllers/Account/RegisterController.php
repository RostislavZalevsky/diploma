<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Subscription\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function index($email = null)
    {
        if (Auth::check()) {
            return redirect($this->getRedirect());
        }

        $this->clearAuth();
        $data = [
            'query' => \Illuminate\Support\Facades\Request::query(),
            'email' => htmlTrimTags($email),
        ];

        return view('account.auth.signup', $data);
    }

    public function signup(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|max:128',
            'last_name' => 'required|max:128',
            // TODO unique user, but without deleted user (ignore soft deleted user)
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:8|confirmed',
            'agree' => 'accepted',
        ]);

        $user = $request->only('first_name', 'last_name', 'email', 'password');
        $user['password'] = Hash::make($user['password']);
        $user = User::create($user);
        $user->sendEmailVerificationNotification();

        $this->clearAuth();
        Auth::attempt($request->only('email', 'password'), true);

        if (Auth::check() && $plan_slug = $request->query('plan')) {
            if ($plan = Plan::where('slug', $plan_slug)->first()) {
                return $this->subscribe($plan->company, $plan);
            }
        }

        return redirect($this->getRedirect());
    }
}
