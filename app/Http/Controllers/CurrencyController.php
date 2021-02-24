<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Validators\ValidateCoin;

use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**POST
     * Registra una nueva cryptomoneda. /currencies/register
     *
     * Recibe nombre de la moneda a través de body para introducirla en la database
     *
     * @return $response Confirmación
     */
    public function createCurrency(Request $request){

        $validator = ValidateCoin::validateCreate();

        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $user = Currency::create([
            //TODO
            'name' => $request->get('name'),
            'acronym' => $request->get('acronym'),
            'value' => $request->get('value'),
        ]);

        //Crear score y asignar

        return $this->successResponse($user,'User Created', 201);
    }

    /**GET
     * Devuelve lista de monedas en forma de Json. /currencies/list
     *
     * Recoge todos los datos de la tabla currencies para mostrar todas las cryptomonedas
     * disponibles en la abse de datos.
     *
     * return $response Lista de monedas
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
