<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use \Firebase\JWT\JWT;

class TradeController extends Controller
{
    /**GET
     * Ver la lista de trades de un usuario
     * 
     * Muestra la lista de trades del usuario. Lo busca por el token y devuelve el nombre de la moneda
     * comprada o vendida, el precio al que se realizó la transacción y la cantidad
     *
     * @return $response La lista de los trades realizados por el usuario
     */
    public function trade_history()
    {
        $headers = getallheaders();

        $api_token = $headers['api_token'];

        $user = User::where('api_token', $headers['api_token'])->get()->first();

        if ($user) {
            for ($i=0; $i < count($user->trade); $i++) { 
                $response [$i] = [
                    "Coin Name" => $user->trade[$i]->name,
                    "Price" => $user->trade[$i]->pivot->price,
                    "Quantity" => $user->trade[$i]->pivot->quantity,
                    "Is sell" => $user->trade[$i]->pivot->is_sale
                ];
            }
            
        }else{
            $response = "no user";
        }
        return response()->json($response);
    }
    
}
