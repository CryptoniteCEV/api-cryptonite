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
	Route::post('/follow/{username}',[UserController::class, 'follow_user'])->middleware('EnsureTokenIsValid');
	Route::get('/followings/list',[UserController::class, 'followings_list'])->middleware('EnsureTokenIsValid');
	Route::post('/update/exp/{newExp}',[UserController::class, 'update_exp'])->middleware('EnsureTokenIsValid');
	Route::post('/update/lvl/{newLvl}',[UserController::class, 'update_lvl'])->middleware('EnsureTokenIsValid');
	Route::post('/trade',[UserController::class, 'trade_coins'])->middleware('EnsureTokenIsValid');
	Route::get('/followers',[UserController::class, 'get_followers'])->middleware('EnsureTokenIsValid');

});

Route::prefix('scores')->group(function () {
	Route::get('/list',[ScoreController::class, 'score_list'])->middleware('EnsureTokenIsValid');

});

Route::prefix('trades')->group(function () {
	Route::get('/history',[TradeController::class, 'trade_history'])->middleware('EnsureTokenIsValid');

});

Route::prefix('wallets')->group(function () {
	Route::post('/deposit',[WalletController::class, 'deposit'])->middleware('EnsureTokenIsValid');
	Route::get('/info',[WalletController::class, 'wallet_info'])->middleware('EnsureTokenIsValid');

});

Route::prefix('currencies')->group(function () {

    Route::get('/register',[WalletController::class, 'create_currency'])->middleware('EnsureTokenIsValid');
	Route::get('/list',[WalletController::class, 'get_coins'])->middleware('EnsureTokenIsValid');

});
