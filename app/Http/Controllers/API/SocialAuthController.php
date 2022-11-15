<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function loginWithGoogle()
    {
        try {
            $user         = Socialite::driver('google')->stateless()->user();
            $existingUser = User::where('google_id', $user->id)->first();

            if ($existingUser) {
                Auth::login($existingUser);
            } else {
                $createUser = User::create([
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'google_id' => $user->id
                ]);

                Auth::login($createUser);
            }

            return redirect()->route('dashboard');
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }
}
