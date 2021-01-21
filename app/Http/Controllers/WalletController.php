<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\currency;
use App\Models\wallet;

class WalletController extends Controller
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
        $response = "";
		$data = $request->getContent();
        $data = json_decode($data);

        if ($data) {
            $headers = getallheaders();

            $user = User::where('api_token', $headers['api_token'])->get()->first();

            $coin = currency::where('name', $data->coin_name)->get()->first();

            if ($user) {
                if ($coin) {
                    $wallet = new Wallet();

                    $wallet->quantity = $data->quantity;
                    $wallet->user_id = $user->id;
                    $wallet->currency_id = $coin->id;

                    try{
                        $wallet->save();
                        $response = "Deposited money";
                    }catch(\Exception $e){
                        $response = $e->getMessage();
                    }

                }else{
                    $response = "No valid coin";
                }
            }else{
                $response = "No valid user";
            }
        }else{
            $response = "No valid data";
        }
        return response($response);
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

        $headers = getallheaders();

        $api_token = $headers['api_token'];

        $user = User::where('api_token', $headers['api_token'])->get()->first();

        if ($user->currency) {
            for ($i=0; $i < count($user->currency); $i++) { 
                $response [$i] = [
                    "Coin Name" => $user->currency[$i]->name,
                    "Quantity" => $user->currency[$i]->pivot->quantity,
                ];
            }            
        }else{
            $response = "No wallet";
        }
        return response($response);
    }
}
