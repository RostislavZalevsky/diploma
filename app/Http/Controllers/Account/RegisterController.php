<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Subscription\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends LoginController
{
    public function index()
    {
        if (Auth::check()) {
            return redirect($this->getRedirect());
        }

        $this->clearAuth();

        return view('account.register');
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|min:3|max:256',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:8|confirmed',
            'agree' => 'accepted',
        ]);

        $user = $request->only('name', 'email', 'password');
        $user['password'] = Hash::make($user['password']);
        $user = User::create($user);
        $user->sendEmailVerificationNotification();

        $this->clearAuth();
        Auth::attempt($request->only('email', 'password'), true);

        return redirect($this->getRedirect());
    }
}
