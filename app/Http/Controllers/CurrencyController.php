<?php

namespace App\Http\Controllers;

use App\Models\Currency;

use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Recibe nombre de la moneda a través de body para introducirla en la database
     */
    public function createCurrency(Request $request){

        $response = "";
		$data = $request->getContent();
        $data = json_decode($data);
        
		if($data){

            $currency = new Currency();
            $currency->name = $data->name;

            try{
                $currency->save();
                $response = "Moneda registrada";
            }catch(\Exception $e){
                $response = $e->getMessage();
            }
		}else{
			$response = "No has introducido una moneda válida";
		}

        return response($response);

    }

    /**
     * Devuelve lista de monedas en forma de Json
     */
    public function getCoins(){

        $request = [];
        $currencies = Currency::all();

        foreach ($currencies as $currency) {
            
            $request = [
                "Name" => $currency->name
                //Próximamente necesitaremos su valor recogido de una api amiga
            ];
        }

        return response()->json($request);
    }
}
