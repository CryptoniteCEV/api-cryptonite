<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\currency;
use App\Models\wallet;

class WalletController extends Controller
{
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
