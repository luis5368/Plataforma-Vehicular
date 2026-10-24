<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActiveSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'max:1024', function ($attribute, $value, $fail) use ($request) {
                if (strcasecmp($value, $request->user()->email) === 0) {
                    $fail('La contraseña no debe ser igual al correo.');
                }
            }],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        app(ActiveSession::class)->start($request->user()->fresh(), $request->session());
        $request->session()->regenerate();

        return back()->with('status', 'password-updated');
    }
}
