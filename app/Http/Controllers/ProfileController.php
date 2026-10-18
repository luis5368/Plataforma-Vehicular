<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->role?->name !== 'admin') {
            abort_if($user->registeredVehicles()->whereHas('theftReport', fn ($q) => $q->where('reported_by', '!=', $user->id))->exists(), 403,
                'La cuenta tiene vehículos con reportes de otra persona. Solicita la desactivación a administración.');
        }

        Auth::logout();

        DB::transaction(function () use ($user, $request) {
            foreach ($user->registeredVehicles()->with('theftReport')->get() as $vehicle) {
                AuditLog::create(['user_id' => $user->id, 'action' => 'delete_with_account', 'table_name' => 'vehicles', 'record_id' => $vehicle->id, 'old_values' => $vehicle->toArray(), 'ip_address' => $request->ip()]);
            }
            AuditLog::create(['user_id' => $user->id, 'action' => 'delete_account', 'table_name' => 'users', 'record_id' => $user->id, 'old_values' => $user->toArray(), 'ip_address' => $request->ip()]);
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
