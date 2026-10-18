<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

// Compatibility adapter: all registration uses the same authorization rules.
class RegisteredUserController extends AuthController
{
    public function create(Request $request)
    {
        return $this->showRegister($request);
    }

    public function store(Request $request)
    {
        return $this->register($request);
    }
}
