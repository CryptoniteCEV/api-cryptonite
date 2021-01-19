<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\ScoreController;


Route::prefix('users')->group(function () {
	Route::post('/register',[UserController::class, 'register']);
	Route::post('/login',[UserController::class, 'login']);
	Route::post('/restore/password',[UserController::class, 'restore_password']);
	Route::post('/update/password',[UserController::class, 'change_password'])->middleware('EnsureTokenIsValid');
	Route::get('profile/info',[UserController::class, 'profile_info'])->middleware('EnsureTokenIsValid');
	Route::get('user/info/{id}',[UserController::class, 'following_info'])->middleware('EnsureTokenIsValid');
	Route::post('/update/profile',[UserController::class, 'update_profile'])->middleware('EnsureTokenIsValid');
	Route::post('/follow/{username}',[UserController::class, 'followUser'])->middleware('EnsureTokenIsValid');
	Route::get('/followings/list',[UserController::class, 'followingsList'])->middleware('EnsureTokenIsValid');
	Route::post('/update/exp/{newExp}',[UserController::class, 'updateExp'])->middleware('EnsureTokenIsValid');
	Route::post('/update/lvl/{newLvl}',[UserController::class, 'updateLvl'])->middleware('EnsureTokenIsValid');
	Route::post('/trade',[UserController::class, 'tradeCoins'])->middleware('EnsureTokenIsValid');
	Route::get('/followers',[UserController::class, 'getFollowers'])->middleware('EnsureTokenIsValid');

});

Route::prefix('scores')->group(function () {
	Route::get('/list',[ScoreController::class, 'scoreList'])->middleware('EnsureTokenIsValid');

});

Route::prefix('trades')->group(function () {
	Route::get('/history',[TradeController::class, 'tradeHistory'])->middleware('EnsureTokenIsValid');

});

Route::prefix('wallets')->group(function () {
	Route::post('/deposit',[WalletController::class, 'deposit'])->middleware('EnsureTokenIsValid');
	Route::get('/info',[WalletController::class, 'walletInfo'])->middleware('EnsureTokenIsValid');

});

Route::prefix('currencies')->group(function () {

    Route::get('/register',[WalletController::class, 'createCurrency'])->middleware('EnsureTokenIsValid');
	Route::get('/list',[WalletController::class, 'getCoins'])->middleware('EnsureTokenIsValid');

});
