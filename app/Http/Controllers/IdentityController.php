<?php

namespace App\Http\Controllers;

use App\Domain\ProfileService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class IdentityController
{
    public function register(Request $request, ProfileService $profiles)
    {
        $input = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'confirmed', Password::min(12)]]);
        $user = DB::transaction(function () use ($input, $profiles) {
            $user = User::create($input);
            $profiles->get($user->id);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['data' => $user], 201);
    }

    public function login(Request $request)
    {
        $input = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($input)) {
            throw ValidationException::withMessages(['email' => ['The credentials do not match.']]);
        }
        $request->session()->regenerate();

        return response()->json(['data' => $request->user()]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()]);
    }
}
