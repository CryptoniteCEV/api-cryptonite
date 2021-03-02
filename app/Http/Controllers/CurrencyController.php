<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Validators\ValidateCoin;
use App\Constants\Coin;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

use App\Helpers\CoinGecko;

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
        
        for ($i=0; $i < count($currencies); $i++) { 

            $response[$i] = [
                "Name" => $currencies[$i]->name,
                "Symbol" => $currencies[$i]->symbol,
                "Price" => CoinGecko::getPrice($currencies[$i]->name, 'usd')
            ];
            if($currencies[$i]->name == "Tether"){
                $response[$i]['Price'] = 1;
            }else{
                $response[$i]['Price'] = CoinGecko::getPrice($currencies[$i]->name, 'usd');
            }
        }

        return $this->successResponse($response, 201);
    }
    public function get_coins_with_quantities(){

        $response = [];
        $currencies = Currency::all();
        
        for ($i=0; $i < count($currencies); $i++) { 

            $response[$i] = [
                "Name" => $currencies[$i]->name,
                "Symbol" => $currencies[$i]->symbol,
            ];
            if($currencies[$i]->wallet){
                $response[$i]['Quantity'] = $currencies[$i]->wallet->quantity;

                if($currencies[$i]->name == "Tether"){
                    $response[$i]['Price'] = $response[$i]['Quantity'];
                }else{
                    $response[$i]['Price'] = CoinGecko::convert_quantity($currencies[$i]->name, $currencies[$i]->wallet->quantity, 1);
                }
            }else{
                $response[$i]['Quantity'] = 0;
                $response[$i]['inDollars'] = 0;
            }
        }

        return $this->successResponse($response, 201);
    }

    public function get_price(Request $request){

        $currentPrice = CoinGecko::getPrice($request->get('coin'), 'usd');

        return $this->successResponse($currentPrice, 201);
    }

    public function convert_quantity(Request $request){

        $converted_quantity = CoinGecko::convert_quantity($request->get('coin'), $request->get('quantity') , $request->get('is_sell'));

        return $this->successResponse($converted_quantity, 201);
    }

    public function get_coin_history(Request $request){

        $coin_history = CoinGecko::getHistoryInDays($request->get('coin'), 'usd' , $request->get('days'));

        return $this->successResponse($coin_history, 201);
    }
}
