<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends LoginController
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
