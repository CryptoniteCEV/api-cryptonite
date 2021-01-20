<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Score;
use App\Models\Currency;
use App\Models\Trade;
use App\Models\Wallet;

use App\Http\Helpers\MyJWT;
use \Firebase\JWT\JWT;



class UserController extends Controller
{
    /**
     * 
     */
    public function register(Request $request){

        $response = "";
		$data = $request->getContent();
        $data = json_decode($data);
        
		if($data){

            $user = new User();
            $score = New Score();

            $user->username = $data->username;
            $user->email = $data->email;
            $user->password = Hash::make($data->password);
            $user->name = $data->name;
            $user->surname = $data->surname;
            $user->profile_pic = isset($data->profile_pic) ? $data->profile_pic : $user->profile_pic;
            
            try{
                $user->save();

                $score->level = 0;
                $score->experience = 0;
                $score->user_id = $user->id;
                $score->save();
                $response = "Usuario registrado";
            }catch(\Exception $e){
                $response = $e->getMessage();
            }

		}else{
			$response = "No has introducido un usuario válido";
		}

        return response($response);

    }
    /**
     * 
     */
    public function login(Request $request){

        $response = "";
		$data = $request->getContent();
        $data = json_decode($data);
        
        $user = User::where('username', $data->username)->get()->first();

        $payload = MyJWT::generatePayload($user);
        $key = MyJWT::getKey();

        $jwt = JWT::encode($payload, $key);
        
		if($data){

            if (Hash::check($data->password, $user->password)) { 

                $response = $jwt;
                $user->api_token = $jwt;
                try{
                    $user->save();
                }catch(\Exception $e){
                    $response = $e->getMessage();
                }
            }else{
                $response = "Usuario o contraseña no coinciden";
            }
        }

        return response($response);

    }

    /**POST
     * 
     */
    public function restore_password(Request $request){
        $response = "";
        //Leer el contenido de la petición
        $data = $request->getContent();
        //Decodificar el json
        $data = json_decode($data);
        // Buscar el usuario por su email
        $user = User::where('email', $data->email)->get()->first();

        if($user){
            // Generar una nueva contraseña aleatoria
            $new_password = Str::random(15);
            // Guardar la contraseña en la bbdd
            $user->password = Hash::make($new_password);

            try{
                $user->save();
                // Se envia la nueva contraseña al usuario
                $response = "Tu nueva contraseña es: ".$new_password;
            }catch(\Exception $e){
                $response = $e->getMessage();
            }
        }else $response = "El email introducido no existe";
        // Enviar la respuesta
        return $response;
    }

    /**POST
     * 
     */
    public function change_password(Request $request){
        $response = "";
        //Leer el contenido de la petición
        $data = $request->getContent();
        //Decodificar el json
        $data = json_decode($data);
        $key = MyJWT::getKey();
        //Decodificar el token
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));

        // Buscar el usuario 
        $user = User::where('username', $decoded->username)->get()->first();

        if($data){
            // Si la contraseña guardada es correcta
            if(Hash::check($data->password, $user->password)){
                // Guardar la nueva contraseña
                $user->password = Hash::make($data->new_password);

                try{
                    $user->save();
                    
                    $response = "OK";
                }catch(\Exception $e){
                    $response = $e->getMessage();
                }
            }
        }
        // Enviar la respuesta
        return $response;
    }

    /**GET
     * 
     */
    public function profile_info(Request $request){
        $response = "";
        //Decodificar el token
        $key = MyJWT::getKey();
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));

        // Buscar el usuario 
        $user = User::where('username', $decoded->username)->get()->first();

        if($user){
            $response = [
                "username" => $user->username,
                "name" => $user->name,
                "surname" => $user->surname,
                "email" => $user->email,
                "profile_pic" => $user->profile_pic
            ];

        } else {
            $response = "Ese usuario no existe";
        }
        // Enviar la respuesta
        return $response;
    }

    /**GET
     * 
     */
    public function following_info(Request $request, $id){
        $response = "";

        // Buscar el usuario 
        $user = User::find($id);

        $response = [];

        if($user){

           $response [] = [
                "username" => $user->username,
                "profile_pic" => $user->profile_pic
            ];
            //
            //
            // HAY QUE REVISAR TODO ESTO, PROBABLEMENTE ES MEJOR PASARLO A TRADE Y HACERLO DESDE AHÍ
            //
            //
            /*for ($i=0; $i < count($user->currency->pivot); $i++) { 
                $response[$i]["price"] = $user->currency->pivot[$i]->price;
                $response[$i]["quantity"] = $user->currency->pivot[$i]->quantity;
                $response[$i]["currency"] = $user->currency->pivot[$i]->currency;
            }*/
              
        } else {
            $response = "Ese usuario no existe";
        }
        // Enviar la respuesta
        return $response;
    }

    /**POST
     * 
     */
    public function update_profile(Request $request){
        $response = "";
        //Leer el contenido de la petición
        $data = $request->getContent();
        //Decodificar el json
        $data = json_decode($data);
        $key = MyJWT::getKey();
        //Decodificar el token
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));

        // Buscar el usuario 
        $user = User::where('username', $decoded->username)->get()->first();

        if($data){
            $user->name = (isset($data->name) ? $data->name : $user->name);
            $user->surname = (isset($data->surname) ? $data->surname : $user->surname);
            $user->profile_pic = (isset($data->profile_pic) ? $data->profile_pic : $user->profile_pic);

            try{
                $user->save();
               
                $response = "OK";
            }catch(\Exception $e){
                $response = $e->getMessage();
            }
            
        }
        // Enviar la respuesta
        return $response;
    }

    /**POST
     * 
     */
    public function follow_user(Request $request, $username){
        $response = "";
        //Leer el contenido de la petición
        $data = $request->getContent();
        //Decodificar el json
        $data = json_decode($data);
        $key = MyJWT::getKey();
        //Decodificar el token
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));

        // Buscar el usuario 
        $user_who_follow = User::where('username', $decoded->username)->get()->first();

        $user_who_is_followed = User::where('username', $data->username)->get()->first();

        if($user_who_is_followed){
            $following = New Following();

            $following->following_id = $user_who_is_followed->id;
            $following->follower_id = $user_who_follow->id;

            try{
                $following->save();
               
                $response = "OK";
            }catch(\Exception $e){
                $response = $e->getMessage();
            }
            
        }else $response = "Ese usuario no existe";
        // Enviar la respuesta
        return $response;
    }

    /**GET
     * 
     */
    public function followings_list(Request $request){
        $response = "";
        //Decodificar el token
        $key = MyJWT::getKey();
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));

        // Buscar el usuario 
        $user = User::where('username', $decoded->username)->get()->first();
        $followings_list = Following::where('follower_id', $user->id)->get();

        $response = [];

        if($followings_list){
            for ($i=0; $i < count($followings_list); $i++) { 
                $user_followed = User::find($followings_list[$i]->following_id);
                $response = [
                "username" => $user_followed->username,
                "profile_pic" => $user_followed->profile_pic
            ];
            }
        } else {
            $response = "No sigues a ningún usuario";
        }
        // Enviar la respuesta
        return $response;
    }

    /**POST
     * 
     */
    public function update_exp(Request $request, $newExp){
        $response = "";
        //Leer el contenido de la petición
        $data = $request->getContent();
        //Decodificar el json
        $data = json_decode($data);
        $key = MyJWT::getKey();
        //Decodificar el token
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));

        // Buscar el usuario 
        $user = User::where('username', $decoded->username)->get()->first();

        if($user){
            $score = Score::where('user_id', $user->id);

            $score->experience = $newExp;            

            try{
                $score->save();               
                $response = "OK";
            }catch(\Exception $e){
                $response = $e->getMessage();
            }
            
        }else $response = "Ese usuario no existe";
        // Enviar la respuesta
        return $response;
    }

    public function update_lvl($new_level){
        $response = "";
        $headers = getallheaders();
        $user = User::where('api_token', $headers['api_token'])->get()->first();

        if ($user) {

            $score = Score::where('user_id', $user->id)->get()->first();
            $score->level = $new_level;

            try{
                $score->save();
               
                $response = "OK";
            }catch(\Exception $e){
                $response = $e->getMessage();
            }
        }else{
            $response = "No valid user";
        }
        return $response;
    }

    public function trade_coins(Request $request){
        $response =[];
        $data = $request->getContent();
        $data = json_decode($data);        

        if ($data) {
            $headers = getallheaders();
            $user = User::where('api_token', $headers['api_token'])->get()->first();
            $trade = new Trade();

            if ($user){                
                for ($i=0; $i < count($user->currency); $i++) { 

                    if ($user->currency && $user->currency[$i]->id == $data->coin_id && $user->currency[$i]->pivot->quantity >= $data->quantity) {

                        $wallet = Wallet::where('id', $user->currency[$i]->id)->get()->first();
                        $wallet->quantity -= $data->quantity;                   
    
                        $trade->price = $data->price;
                        $trade->quantity = $data->quantity;
                        $trade->user_id = $user->id;
                        $trade->currency_id = $data->coin_id;
    
                        try{
                            $trade->save();
                            $wallet->save();
                            $response = "Trade succesful";
                        }catch(\Exception $e){
                            $response = $e->getMessage();
                        }
                    }else{
                        $response = "no";
                    }
                }
                               
            }else{
                $response = "No valid user";
            }
        }
        return response()->json($response);
    }
}
 