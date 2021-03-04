<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\CurrencyController;
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
	Route::post('/trade/coin',[UserController::class, 'trade_coin'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('/trades/info',[UserController::class, 'trades_info'])->middleware('EnsureTokenIsValid');// Done	
	Route::get('/trades/profile/info',[UserController::class, 'trades_profile_info'])->middleware('EnsureTokenIsValid');// Done	

});

Route::prefix('scores')->group(function () {
	Route::get('/list',[ScoreController::class, 'score_list'])->middleware('EnsureTokenIsValid'); // Done
});

Route::prefix('trades')->group(function () {
	Route::get('/history',[TradeController::class, 'trade_history'])->middleware('EnsureTokenIsValid'); // Done
});

Route::prefix('wallets')->group(function () {
	Route::put('/deposit',[WalletController::class, 'deposit'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('/info',[WalletController::class, 'wallet_info'])->middleware('EnsureTokenIsValid'); // Done
});

Route::prefix('coins')->group(function () {
	Route::post('/create',[CurrencyController::class, 'create_currency'])->middleware('EnsureTokenIsValid'); // Done
	Route::post('/generate/all',[CurrencyController::class, 'generate_currencies']);//->middleware('EnsureTokenIsValid'); // Done
	Route::get('/list',[CurrencyController::class, 'get_coins'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('/get/price',[CurrencyController::class, 'get_price'])->middleware('EnsureTokenIsValid'); // Done
	Route::get('/convert/quantity',[CurrencyController::class, 'convert_quantity'])->middleware('EnsureTokenIsValid');
	Route::get('/history',[CurrencyController::class, 'get_coin_history'])->middleware('EnsureTokenIsValid');
	Route::get('/quantities',[CurrencyController::class, 'get_coins_with_quantities'])->middleware('EnsureTokenIsValid');
});
