<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Subscription\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class LoginController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect($this->getRedirect());
        }

        $this->clearAuth();

        return view('account.login');
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email'   => 'required|email',
            'password'  => 'required'
        ]);

        $this->clearAuth();
        Auth::attempt($request->only('email', 'password'), true);

        return Auth::check()
            ? redirect($this->getRedirect())
            : back()
                ->withInput($request->except('password'))
                ->with('error', 'Wrong Login Details');
    }

    public function logout()
    {
        $this->clearAuth();

        return redirect(route('login.index'));
    }

    protected function clearAuth()
    {
        Auth::logout();
        Session::forget('password_hash');
    }

    protected function getRedirect()
    {
        if (!Auth::check()) {
            return route('login.index');
        }

        return
            !empty(URL::previous())
            && !strpos(URL::previous(), 'login')
            && !strpos(URL::previous(), 'signup')
            && !strpos(URL::previous(), 'email')
                ? URL::previous()
                : $this->defaultRoute();
    }

    private function defaultRoute()
    {
        return route(
            Auth::user()->isSubscribed()
            && Auth::user()->subscription()->isActive()
                ? 'subscription.index'
                : 'prices.index'
        );
    }
}
