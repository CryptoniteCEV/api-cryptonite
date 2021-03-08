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
        $trades = trade::all();
        
        for ($i=0; $i < count($trades); $i++) { 
            $info[$i] = [
                "Quantity" => $trades[$i]->quantity,
                "Username" => $trades[$i]->user->username,
                "Converted" => CoinGecko::convert_quantity($trades[$i]->currency->name, $trades[$i]->quantity, $trades[$i]->is_sell)
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
