<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\trade;

use App\Http\Controllers\ApiController;

use App\Helpers\CoinGecko;

class TradeController extends ApiController
{
    /**GET
     * Ver la lista de trades de un usuario
     * 
     * Muestra la lista de trades global. Lo busca por el token y devuelve el nombre de la moneda
     * comprada o vendida, el precio al que se realizó la transacción y la cantidad
     *
     * @return $response La lista de los trades realizados por el usuario
     */
    public function trade_history()
    {
        $info = [];
       
        $trades = trade::orderBy('created_at', 'desc')->get();

        for ($i=0; $i < count($trades); $i++) { 
            $info[$i] = [
                "Quantity" => $trades[$i]->quantity,
                "Username" => $trades[$i]->user->username,
                "Profile_pic" => $trades[$i]->user->profile_pic,
                "Converted" => $trades[$i]->quantity * $trades[$i]->price,
            ];
            if($trades[$i]->is_sell ==1){
                $info[$i]["Coin_from"] = $trades[$i]->currency->name;
                $info[$i]["Coin_from_symbol"] = $trades[$i]->currency->symbol;
                $info[$i]["Coin_to_symbol"] = "$";
                $info[$i]["Coin_to"] = "Tether";
            }else{
                $info[$i]["Coin_to"] = $trades[$i]->currency->name;
                $info[$i]["Coin_from"] = "Tether";
                $info[$i]["Coin_to_symbol"] = $trades[$i]->currency->symbol;
                $info[$i]["Coin_from_symbol"] = "$";
            }
        }
            
        return $this->successResponse($info, 201);
    }
    
}
