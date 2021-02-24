<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\ScoreController;


Route::prefix('users')->group(function () {
	Route::post('/register',[UserController::class, 'register']); // Done
	Route::post('/login',[UserController::class, 'login']); // Done
	Route::put('/restore/password',[UserController::class, 'restore_password']); // Done
	Route::put('/update/password',[UserController::class, 'change_password'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('profile/info',[UserController::class, 'profile_info'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('user/info/{id}',[UserController::class, 'following_info'])->middleware('EnsureTokenIsValid'); // ¡¡¡¡¡¡NO HECHO!!!!!!!
	Route::put('/update/profile',[UserController::class, 'update_profile'])->middleware('EnsureTokenIsValid'); // Done
	Route::post('/follow',[UserController::class, 'follow_user'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('/all',[UserController::class, 'index']);
	Route::get('/followings/list',[UserController::class, 'get_followings'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('/followers/list',[UserController::class, 'get_followers'])->middleware('EnsureTokenIsValid'); // Done
	Route::put('/update/exp',[UserController::class, 'update_user_exp'])->middleware('EnsureTokenIsValid');// Done

	Route::put('/update/lvl/{newLvl}',[UserController::class, 'update_lvl'])->middleware('EnsureTokenIsValid');// Done	
	Route::post('/trade',[UserController::class, 'trade_coins'])->middleware('EnsureTokenIsValid'); // Done
	
	
	Route::delete('/delete',[UserController::class, 'delete_user'])->middleware('EnsureTokenIsValid');

});

Route::prefix('scores')->group(function () {
	Route::get('/list',[ScoreController::class, 'score_list'])->middleware('EnsureTokenIsValid'); // Done

});

Route::prefix('trades')->group(function () {
	Route::get('/history',[TradeController::class, 'trade_history'])->middleware('EnsureTokenIsValid'); // Done

});

Route::prefix('wallets')->group(function () {
	Route::post('/deposit',[WalletController::class, 'deposit'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('/info',[WalletController::class, 'wallet_info'])->middleware('EnsureTokenIsValid'); // Done

});

Route::prefix('currencies')->group(function () {
    Route::post('/register',[WalletController::class, 'create_currency'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('/list',[WalletController::class, 'get_coins'])->middleware('EnsureTokenIsValid'); // Done

});
