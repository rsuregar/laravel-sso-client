<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::view('/', 'welcome');

Route::get('prepare-to-login', function (Request $request) {

    $request->session()->put('state', $state = Str::random(40));

    $query = http_build_query([
        'client_id' => config('passport.client_id'),
        'redirect_uri' => config('passport.redirect_uri'),
        'response_type' => 'code',
        'scope' => '',
        'state' => $state,
    ]);

    // dd(config('passport.client_secret'));


    return redirect(config('passport.api_uri').'oauth/authorize?'.$query);
})->name('prepare.login');

Route::get('auth/callback', function(Request $request){
    // dd($request->all());

    // verification state
    $state = $request->session()->pull('state');
    $response = Http::withOptions([
        // 'debug' => true,
        'verify' => false
    ])->asForm()->post(config('passport.api_uri').'oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => config('passport.client_id'),
        'client_secret' => config('passport.client_secret'),
        'redirect_uri' => config('passport.redirect_uri'),
        'code' => $request->code,
    ]);

    $data = $response->json();

    // dd($data);

    //get User by user API
    $user = Http::withHeaders([
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$data['access_token'],
    ])
    ->withoutVerifying()
    ->get('https://sso.test/api/user');

    // dd($user->object());

    //adding to localdb
    $add = \App\Models\User::firstOrCreate([
        'email' => $user['email']
    ],[
        'name' => $user['name'],
        'password' => bcrypt('password123')
    ]);

    //loginUsingId
    \Auth::login($add);
    if (\Auth::check()) {
        return redirect('/home');
    }

});


Route::get('grant-password', function () {
    $response = Http::withoutVerifying()
    ->asForm()->post(config('passport.api_uri').'oauth/token', [
        'grant_type' => 'password',
        'client_id' => 4,
        'client_secret' => 'kf6iZjnGauYjWv0LEwa5lI6R1DSRyUELvstpYkpK',
        'username' => 'rahmat',
        'password' => '12345678',
        'scope' => '',
    ]);

    // dd ($response->json());

    $data = $response->json();

    // return ($data['access_token']);

     //get User by user API
     $user = Http::withHeaders([
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$data['access_token'],
    ])
    ->withoutVerifying()
    ->get('https://sso.test/api/user');

    // dd($user->object());

    //adding to localdb
    $add = \App\Models\User::firstOrCreate([
        'email' => $user['email']
    ],[
        'name' => $user['name'],
        'password' => bcrypt('password123')
    ]);

    //loginUsingId
    // dd($add);
    \Auth::login($add);
    if (\Auth::check()) {
        return redirect('/home');
    }
})->name('auth.password');


Route::get('grant-client', function () {
    $response = Http::withoutVerifying()
    ->asForm()->post(config('passport.api_uri').'oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => 3,
        'client_secret' => '8uAfynTVg38HaVY9xMhagPDeo2DoulhBk0p0hOZ6',
        'scope' => '',
    ]);

    // return ($response->json());

    $data = $response->json();

    // return ($data['access_token']);

     //get User by user API
     $user = Http::withHeaders([
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$data['access_token'],
    ])
    ->withoutVerifying()
    ->get('https://sso.test/api/client');

    dd($user->object());

    //adding to localdb
    $add = \App\Models\User::firstOrCreate([
        'email' => $user['email']
    ],[
        'name' => $user['name'],
        'password' => bcrypt('password123')
    ]);

    //loginUsingId
    // dd($add);
    \Auth::login($add);
    if (\Auth::check()) {
        return redirect('/home');
    }
})->name('auth.client');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
