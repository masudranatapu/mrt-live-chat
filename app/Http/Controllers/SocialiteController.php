<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SocialiteController extends Controller
{
    //

    public function googleRedirect()
    {
        $googleData =  Socialite::driver('google')
            ->setHttpClient(new Client(['verify' => false]))
            ->redirect();
        return $googleData;
    }

    public function googleCallback()
    {
        $user = Socialite::driver('google')
            ->setHttpClient(new Client(['verify' => false]))
            ->user();

        $findUser = User::where('email', $user->email)->first();

        if ($findUser) {
            Auth::login($findUser);
            return redirect()->route('dashboard');
        } else {
            $newUser = new User();
            $newUser->name = $user->name;
            $newUser->email = $user->email;
            $newUser->password = Hash::make('password');
            $newUser->save();
            Auth::login($newUser);

            return redirect()->route('dashboard');
        }
    }
}
