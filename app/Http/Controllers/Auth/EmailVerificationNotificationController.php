<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\VerificationMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // The standard notification is synchronous: only report success after dispatch.
        if (! VerificationMail::dispatch(fn () => $request->user()->sendEmailVerificationNotification(), $request->user()->id)) {
            $request->session()->forget('status');

            return back()->withErrors(['verification' => __('verification.failed', [], 'es')]);
        }

        $transport = config('mail.mailers.'.config('mail.default').'.transport');
        $status = match ($transport) {
            'log' => 'verification-link-logged',
            'array' => 'verification-link-local',
            default => 'verification-link-sent',
        };

        return back()->with('status', $status);
    }
}
