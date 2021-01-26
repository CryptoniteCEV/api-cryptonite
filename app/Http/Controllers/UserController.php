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
    /**POST
     * Registro de usuarios en la app. /users/register 
     *
     * Llega en la petición el username, email y contraseña, nombre y apellidos y ¿fecha de nacimiento?
     * Añade el nuevo usuario en la base de daos si la información es correcta.
     * 
     * @param $request Petición con los datos del usuario
     * @return $response respuesta de la api OK/NOOK
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

        return response()->json($response);

    }
    /** POST
     * Login de usuarios en la app. /users/login
     *
     * Llega en la petición el username y contraseña del usuario, si son correctos, 
     * obtiene acceso a la app y se genera su token.
     *
     * @param $request Petición con los datos de login del usuario
     * @return $response Respuesta de la api con el token del usuario 
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

        return response()->json($response);

    }

    /**POST
     * Restaurar contraseña del usuario que no puede hacer login. /users/restore/password
     *
     * Llega en la peticion el email del usuario que ha olvidado la contraseña. Se genera una nueva contraseña 
     * aleatoria y se le envia a su email
     *
     * @param $request Petición con el email del usuario
     * @return $response Respuesta de la api con la nueva contraseña del usuario
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
        }else {
            $response = "El email introducido no existe";
        }
        // Enviar la respuesta
        return response()->json($response);
    }

    /**POST
     * Cambiar la contraseña del usuario desde los ajustes de perfil de la app. /users/update/password
     * 
     * Llega en la petición la antigua contraseña del usuario y la nueva, si la contraseña antigua es correcta,
     * se cambia por la nueva contraseña.
     *
     * @param $request Petición con la antigua contraseña del usuario y la nueva
     * @return $response Respuesta de la api OK/Contraseña incorrecta
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
            }else $response = "Contraseña incorrecta";
        }
        // Enviar la respuesta
        return response()->json($response);
    }

    /**GET
     * Ver la informacion del perfil. /users/profile/info
     * 
     * Se obtiene el usuario que hace la petición por su token y se envian sus 
     * datos de usuario.
     *
     * @param $request 
     * @return $response Respuesta de la api con la información del usuario
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
        return response()->json($response);
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
        return response()->json($response);
    }

    /**POST
     * Cambiar los datos del usuario. /users/update/profile
     *
     * Llega en la petición la información que el usuario quiere modificar y se
     * actualiza en la base de datos.
     *
     * @param $request Petición con los datos a modificar del usuario
     * @return $response Confirmación de los cambios
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
        return response()->json($response);
    }

    /**POST
     * Seguir a un usuario. /users/follow/{username}
     * 
     * Llega por url el nombre de usuario al que se va a seguir y se decodifica el token para obtener
     * el id del usuario que va a seguir. Se crea la fila en la tambla de followings.
     * 
     * @param $request
     * @param $username Nombre del usuario al que se va a seguir
     * @return $response Confirmación de seguimiento
     */
    public function follow_user(Request $request, $username){
        $response = "";
        
        $key = MyJWT::getKey();
        //Decodificar el token
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));

        // Buscar el usuario 
        $user_who_follow = User::where('username', $decoded->username)->get()->first();

        $user_who_is_followed = User::where('username', $username)->get()->first();

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
        return response()->json($response);
    }

    /**GET
     * Ver la lista de usarios a los que sigues. /users/followings/list
     *
     * Se obtiene el usuario que realiza la peticion decodificando su token y se comprueban los
     * usuarios a los que sigue en la fabla followings con su id de usuario.
     *
     * @param $request
     * @return $response Lista de los usuarios a los que sigue o No sigues a nadie
     */
    public function followings_list(Request $request){
        $response = [];
        //Decodificar el token
        $key = MyJWT::getKey();
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));

        // Buscar el usuario 
        $user = User::where('username', $decoded->username)->get()->first();
        $followings_list = Following::where('follower_id', $user->id)->get();
        

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
        return response()->json($response);
    }

    /**POST
     * Actualizar la experiencia del usuario. /users/update/exp/{newExp}
     *
     * Obtiene el usuario por su token y actualiza su experiencia (xp) que llega por url
     * en la tabla de scores.
     *
     * @param $request
     * @param $newExp La cantidad de experiencia que tiene el usuario para actualizar en la tabla scores
     * @return $response Mensaje de confirmación
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
        return response()->json($response);
    }

    /**POST
     * Actualizar el nivel del usuario. /users/update/lvl/{newLvl}
     * 
     * Obtiene el usuario por su token y actualiza su nivel (lvl) que llega por url
     * en la tabla de scores.
     * 
     * @param $request
     * @param $new_level Nivel al que se va a actualzar en la tabla scores
     * @return $response Mensaje de confirmación
     */
    public function update_lvl(Request $request, $new_level){
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
        return response()->json($response);
    }

    /**POST
     * Vender cantidad de cryptos que pesea el usuario en su wallet
     * Falta hacer que se pueda comprar
     * 
     */
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

    /**GET
     * Ver la lista de tus seguidores /users/followers
     *
     * Se obtiene el usuario que realiza la peticion decodificando su token y se comprueban los
     * usuarios que le siguen en la fabla followings con su id de usuario en following_id.
     *
     * @param $request
     * @return $response Lista con los seguidores/ No tienes seguidores 
     */
    public function get_followers(Request $request) {
        $response =[];

        $key = MyJWT::getKey();
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));

        $user = User::where('username', $decoded->username)->get()->first();
        $followers_list = Following::where('following_id', $user->id)->get();

        if($followers_list){
            for ($i=0; $i < count($followers_list); $i++) { 
                $user_follower = User::find($followers_list[$i]->follower_id);
                $response = [
                "username" => $user_follower->username,
                "profile_pic" => $user_follower->profile_pic
            ];
            }
        } else {
            $response = "No te sigue ningún usuario";
        }
        return response()->json($response);
    }

    /**
     * Devuelve todos los usuarios
     */
    public function get_users() {
        $response =[];
        $users = User::all();

        if($users){
            foreach ($users as $user) {
                $response = [
                    "username" => $user->username,
                    "profile_pic" => $user->profile_pic,
                    "name" => $user->name,
                    "surname" => $user->surname,
                    "email" => $user->email
                ];
            }
        } else {
            $response = "No hay users";
        }
        return response()->json($users);
    }
}
 