<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect($this->getRedirect())->with(['success' => 'Email verified successfully!']);
    }

    public function verification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail())
            return back()->withErrors('Email is verified');

        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Verification link sent!');
    }
}
