<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\user;
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
     * depositar y la cantidad
     *
     * @param $request La petición con los datos del deposito
     * @return $response Confirmación del depósito
     */
    public function deposit(Request $request){

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));
        
        try{
            $dollar = currency::where('name', 'tether')->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("Coin not found",401);
        }
        try{
            $wallet = wallet::where('currency_id', $dollar->id)->where('user_id',$decoded->id)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("Wallet not found",401);
        }

        $wallet->quantity += $request->get('quantity');
        $wallet->save();

        return $this->successResponse($wallet, 'Successfully deposited', 200);
    }

    /**GET
     * Recibe la información de la cartera del usuario. /wallets/info
     *
     * Devuelve todas las monedas y sus cantidades del usuario recibido en token
     */
    public function wallet_info(){
        $info = [];
        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));
        $cash = 0;
        $user = user::findOrFail($decoded->id);

        for ($i=0; $i < count($user->wallet); $i++) { 
            $info["Wallets"][$i] = [
                "Name" => $user->wallet[$i]->name,
                "Symbol" => $user->wallet[$i]->symbol,
                "Quantity" => $user->wallet[$i]->pivot->quantity,
            ];

            if($user->wallet[$i]->name == "Tether"){
                $info["Wallets"][$i]['inDollars'] = $user->wallet[$i]->pivot->quantity;
                $cash += $user->wallet[$i]->pivot->quantity;
            }else{
                $info["Wallets"][$i]['inDollars'] = CoinGecko::convert_quantity($user->wallet[$i]->name, $user->wallet[$i]->pivot->quantity , 1);
                $cash += $info["Wallets"][$i]['inDollars'];
            }
        } 
        $info["Cash"] = $cash;

        return $this->successResponse($info ,200);
    }

    /**
     * Calcula y devuelve el cash total de un usuario
     */
    public function get_total_cash(){
        $info = [];
        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));
        $cash = 0;
        $user = user::findOrFail($decoded->id);

        for ($i=0; $i < count($user->wallet); $i++) { 

            if($user->wallet[$i]->name == "Tether"){
                $cash += $user->wallet[$i]->pivot->quantity;
            }else{
                $converted = CoinGecko::convert_quantity($user->wallet[$i]->name, $user->wallet[$i]->pivot->quantity , 1);
                $cash += $converted;
            }
        } 
        
        return $this->successResponse((String)$cash ,200);
    }

    /**
     * calcula y devuelve los porcentajes de un usuario recibido en params
     */
    public function get_percentages(Request $request){

        $info = [];
        $cash = 0;
        $user = user::where('username', $request->get('username'))->get()->first();
        $index = 0;
        $wallets = [];

        for ($i=0; $i < count($user->wallet); $i++) { 
            if($user->wallet[$i]->pivot->quantity > 0){
                $info["Wallets"][$index] = [
                    "Symbol" => $user->wallet[$i]->symbol
                ];

                if($user->wallet[$i]->name == "Tether"){
                    $info["Wallets"][$index]['Percentage'] = $user->wallet[$i]->pivot->quantity;
                    $cash += $user->wallet[$i]->pivot->quantity;
                }else{
                    $info["Wallets"][$index]['Percentage'] = CoinGecko::convert_quantity($user->wallet[$i]->name, $user->wallet[$i]->pivot->quantity , 1);
                    $cash += $info["Wallets"][$index]['Percentage'];
                }
                $index += 1;
            }
            
        } 
        
        for ($i=0; $i < count($info["Wallets"]); $i++) { 
            $percentage = ($info["Wallets"][$i]['Percentage'] * 100) / $cash;
            
            $info["Wallets"][$i]['Percentage'] = $percentage;
            
        }

        $index = 0;

        for ($i=0; $i < count($info["Wallets"]); $i++) { 
            if($info["Wallets"][$i]['Percentage'] > 2){
                $wallets["Wallets"][$index]['Symbol'] = $info["Wallets"][$i]['Symbol'];
                $wallets["Wallets"][$index]['Percentage'] = $info["Wallets"][$i]['Percentage'];
                $index += 1;
            }
        }

        return $this->successResponse($wallets ,200);
        
    }

    /**
     * Calcula y devuelve los porcentajes que posee de cada moneda el usuario recibido en el token
     */
    public function get_own_percentages(Request $request){

        $info = [];
        $cash = 0;
        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));
        $user = user::findOrFail($decoded->id);
        $index = 0;
        $wallets = [];

        for ($i=0; $i < count($user->wallet); $i++) { 
            if($user->wallet[$i]->pivot->quantity > 0){
                $info["Wallets"][$index] = [
                    "Symbol" => $user->wallet[$i]->symbol
                ];

                if($user->wallet[$i]->name == "Tether"){
                    $info["Wallets"][$index]['Percentage'] = $user->wallet[$i]->pivot->quantity;
                    $cash += $user->wallet[$i]->pivot->quantity;
                }else{
                    $info["Wallets"][$index]['Percentage'] = CoinGecko::convert_quantity($user->wallet[$i]->name, $user->wallet[$i]->pivot->quantity , 1);
                    $cash += $info["Wallets"][$index]['Percentage'];
                }
                $index += 1;
            }
            
        } 
        
        for ($i=0; $i < count($info["Wallets"]); $i++) { 
            $percentage = ($info["Wallets"][$i]['Percentage'] * 100) / $cash;
            
            $info["Wallets"][$i]['Percentage'] = $percentage;
            
        }

        $index = 0;

        for ($i=0; $i < count($info["Wallets"]); $i++) { 
            if($info["Wallets"][$i]['Percentage'] > 2){
                $wallets["Wallets"][$index]['Symbol'] = $info["Wallets"][$i]['Symbol'];
                $wallets["Wallets"][$index]['Percentage'] = $info["Wallets"][$i]['Percentage'];
                $index += 1;
            }
        }

        return $this->successResponse($wallets ,200);
        
    }
    /**
     * Deposita doges en el usuario recibido en el token
     */
    public function depositDoge(Request $request){

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));
        
        try{
            $doge = currency::where('name', 'DogeCoin')->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("Coin not found",401);
        }
        
        try{
            $wallet = wallet::where('currency_id', $doge->id)->where('user_id',$decoded->id)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("Wallet not found",401);
        }

        $wallet->quantity += $request->get('quantity');
        $wallet->save();

        return $this->successResponse($wallet, 'Successfully deposited', 200);
    }
}
