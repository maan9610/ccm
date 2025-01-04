<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InstagramController;

use App\Http\Controllers\YoutubeController;
use App\Models\Advertisers;

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

Route::get('/', function () {
    return view('welcome');
});


Route::get('/login', [UserController::class, 'login'])->name('user.login');

Route::get('/urlcheck', [UserController::class, 'urlcheck']);

Route::get('/instagram/media', [InstagramController::class, 'getUserMedia'])->name('instagram.getUserMedia');
Route::get('/instagram/redirect', [InstagramController::class, 'redirect'])->name('instagram.redirect');
Route::get('/instagram/callback', [InstagramController::class, 'callback'])->name('instagram.callback');
Route::post('/instagram/deauthorize', [InstagramController::class, 'deauthorize'])->name('instagram.deauthorize');
Route::post('/instagram/data-deletion', [InstagramController::class, 'dataDeletion'])->name('instagram.dataDeletion');


Route::get('/youtube/channel/{id}', [YouTubeController::class, 'getChannel']);
Route::get('/youtube/video/{id}', [YouTubeController::class, 'getVideo']);

Route::get('/verify-business-email/{token}', function ($token) {
    $advDetails = Advertisers::where('verification_token', $token)->first();

    if (!$advDetails) {
        return response()->json(['message' => 'Invalid or expired token.'], 404);
    }

    // Mark the email as verified
    $advDetails->email_verified_at = now();
	$advDetails->isBusinessEmailVerified = 1;
    $advDetails->verification_token = null; // Clear the token
    $advDetails->save();

    return response()->json(['message' => 'Business email verified successfully!']);
})->name('business.verify');