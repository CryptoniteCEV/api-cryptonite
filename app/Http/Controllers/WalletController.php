<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\currency;
use App\Models\wallet;

use \Firebase\JWT\JWT;
use \App\Helpers\Token;
use App\Validators\ValidateWallet;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;

use \App\Helpers\InitiateEntry;
use App\Helpers\CoinGecko;

class WalletController extends ApiController
{
    /**POST
     * Depositar una cantidad de "x" moneda en la cartera del usuario /wallets/deposit
     *
     * Busca al usuario por su api_token, recibe en la petición la currency que se va a 
     * depositar y la cantidad, y se añade a la tabla wallet añadiendo ademas el user_id
     *
     * @param $request La petición con los datos del deposito
     * @return $response Confirmación del depósito
     */
    public function deposit(Request $request){

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        $validator = ValidateWallet::validate_create();

        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $dollar = currency::where('symbol', 'usd')->firstOrFail();
        $wallet = wallet::where('currency_id', $dollar->id)->where('user_id',$decoded->id)->firstOrFail();

        $wallet->quantity += $request->get('quantity');
        $wallet->save();

        return $this->successResponse($wallet, 'Successfully deposited', 201);
    }

    /**GET
     * Recibe la información de la cartera del usuario. /wallets/info
     *
     * Busca al usuario por su api_token, si tiene alguna cryptomoneda, busca los
     * datos en la tabla wallet y devuelve el nombre de la moneda y la cantidad de la misma
     *
     * @return $response en caso de que tenga alguna cryptomoneda, devuelve los datos de la cartera del usuario
     */
    public function wallet_info(){
        $info = [];
        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));
        $cash = 0;
        $user = user::find($decoded->id);

        for ($i=0; $i < count($user->wallet); $i++) { 
            $info[$i] = [
                "Coin" => $user->wallet[$i]->name,
                "Symbol" => $user->wallet[$i]->symbol,
                "Quantity" => $user->wallet[$i]->pivot->quantity,
            ];

            if($user->wallet[$i]->name == "Tether"){
                $info[$i]['Price'] = $user->wallet[$i]->pivot->quantity;
                $cash += $user->wallet[$i]->pivot->quantity;
            }else{
                $info[$i]['Price'] = CoinGecko::convert_quantity($user->wallet[$i]->name, $user->wallet[$i]->pivot->quantity , 1);
                $cash += $info[$i]['Price'];
            }
        } 

        return $this->successResponse($info, $cash ,201);
    }
}
