<?php

namespace App\Http\Controllers;

use App\Models\currency;
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
     * @return $response Confirmación
     */
    public function generate_currencies(){
        $coin = new Coin();
        $cryptos = $coin->get_all();
        foreach ($cryptos as $crypto) {
            $currencies = currency::create([
                'name' => $crypto['name'],
                'symbol' => $crypto['symbol']
            ]);
        }
        return $this->successResponse($cryptos,'Cryptos created', 200);
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

        $coin = currency::create([
            'name' => $request->get('name'),
            'symbol' => $request->get('symbol')
        ]);

        return $this->successResponse($coin,'coin Created', 200);
    }

    /**GET
     * Devuelve lista de monedas en forma de Json. /currencies/list
     *
     * Recoge todos los datos de la tabla currencies para devolver todas las cryptomonedas
     * disponibles en la abse de datos.
     *
     * return $response Lista de monedas
     */
    public function get_coins(){

        $response = [];
        $currencies = currency::all();
        
        for ($i=0; $i < count($currencies); $i++) { 

            $coinInfo = CoinGecko::getAllCoinInfo($currencies[$i]->name);

            $response[$i] = [
                "Name" => $currencies[$i]->name,
                "Symbol" => $currencies[$i]->symbol,
                "Price" => $coinInfo['usd'],
                "Change" => $coinInfo['usd_24h_change']
            ];

            if($currencies[$i]->name == "Tether"){
                $response[$i]['Price'] = 1;
            }else{
                $response[$i]['Price'] = CoinGecko::getPrice($currencies[$i]->name, 'usd');
            }
        }

        return $this->successResponse($response, 200);
    }

    /**
     * Devuelve las monedas y la cantidad que posee el wallet
     */
    public function get_coins_with_quantities(){

        $response = [];
        $currencies = currency::all();
        
        for ($i=0; $i < count($currencies); $i++) { 

            $response[$i] = [
                "Name" => $currencies[$i]->name,
                "Symbol" => $currencies[$i]->symbol,
            ];
            if($currencies[$i]->wallet){
                $response[$i]['Quantity'] = $currencies[$i]->wallet->quantity;

                if($currencies[$i]->name == "Tether"){
                    $response[$i]['inDollars'] = $response[$i]['Quantity'];
                }else{
                    $response[$i]['inDollars'] = CoinGecko::convert_quantity($currencies[$i]->name, $currencies[$i]->wallet->quantity, 1);
                }
            }else{
                $response[$i]['Quantity'] = 0;
                $response[$i]['inDollars'] = 0;
            }
        }

        return $this->successResponse($response, 200);
    }

    /**
     * Devuelve el precio de la moneda recibida por param
     */
    public function get_price(Request $request){

        $currentPrice = CoinGecko::getPrice($request->get('coin'), 'usd');

        return $this->successResponse($currentPrice, 200);
    }

    /**
     * Devuelve la info de la moneda especificada, volume, change y market cap
     */
    public function get_info(Request $request){

        try{
            $currency = currency::where('name', $request->get('name'))->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("Coin not found",401);
        }

        $coinInfo = CoinGecko::getAllCoinInfo($currency->name);
        
        $coin = [
            "Name" => $currency->name,
            "Symbol" => $currency->symbol,
            "Price" => $coinInfo['usd'],
            "Change" => $coinInfo['usd_24h_change'],
            "Volume" => $coinInfo['usd_24h_vol'],
            "Cap" => $coinInfo['usd_market_cap']
        ];

        return $this->successResponse($coin, 200);
    }

    /**
     * Convierte la cantidad de cierta moneda a crypto o a dollar dependiendo de si es compra o venta
     */
    public function convert_quantity(Request $request){

        $converted_quantity = CoinGecko::convert_quantity($request->get('coin'), $request->get('quantity') , $request->get('is_sell'));

        return $this->successResponse($converted_quantity, 200);
    }

    /**
     * Devuelve el historial de los ultimos 30 dias de una moneda
     */
    public function get_coin_history(Request $request){

        $coin_history = CoinGecko::getMarketChart($request->get('coin'));

        return $this->successResponse($coin_history, 200);
    }
}
