<?php

namespace App\Http\Middleware;

use App\Services\ActiveSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SingleActiveSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->is_active && ! app(ActiveSession::class)->matches($user, $request->session())) {
            // Keep the new account's remember cookie valid: do not rotate its DB token here.
            Auth::logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('session-replaced', 'Tu sesión fue reemplazada o revocada. Inicia sesión nuevamente para continuar.');
        }

        return $next($request);
    }
}
