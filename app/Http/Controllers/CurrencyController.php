<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Validators\ValidateCoin;
use App\Constants\Coin;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class CurrencyController extends ApiController
{
    /**POST
     * Registra todas las cryptos creadas previamente a la vez.
     *
     * Recibe nombre de la moneda a través de body para introducirla en la database
     *
     * @return $response Confirmación
     */
    public function generate_currencies(){
        $coin = new Coin();
        $cryptos = $coin->get_all();
        foreach ($cryptos as $crypto) {
            $currencies = Currency::create([
                'name' => $crypto['name'],
                'symbol' => $crypto['symbol']
            ]);
        }
        return $this->successResponse($cryptos,'Cryptos created', 201);
    }
    /**POST
     * Registra una nueva cryptomoneda. /currencies/register
     *
     * Recibe nombre de la moneda a través de body para introducirla en la database
     *
     * @return $response Confirmación
     */
    public function create_currency(Request $request){

        $validator = ValidateCoin::validate_create();

        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $coin = Currency::create([
            'name' => $request->get('name'),
            'symbol' => $request->get('symbol')
        ]);

        return $this->successResponse($coin,'coin Created', 201);
    }

    /**GET
     * Devuelve lista de monedas en forma de Json. /currencies/list
     *
     * Recoge todos los datos de la tabla currencies para mostrar todas las cryptomonedas
     * disponibles en la abse de datos.
     *
     * return $response Lista de monedas
     */
    public function get_coins(){

        $response = [];
        $currencies = Currency::all();

        foreach ($currencies as $currency) {
            
            $response[] = [
                "Name" => $currency->name,
                "Symbol" => $currency->symbol
                //precio
            ];
        }

        return $this->successResponse($response, 201);
    }
}
