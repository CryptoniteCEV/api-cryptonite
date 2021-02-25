<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Trade;

use App\Http\Controllers\ApiController;

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
        $trades = Trade::all();
        
        for ($i=0; $i < count($trades); $i++) { 
            $info[$i] = [
                "Coin Name" => $trades[$i]->currency->name,
                "Price" => $trades[$i]->price,
                "Quantity" => $trades[$i]->quantity,
                "Is sell" => $trades[$i]->is_sell,
                "User" => $trades[$i]->user->name
            ];
        }
            
        return $this->successResponse($info, 201);
    }
    
}
