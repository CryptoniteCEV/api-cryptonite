<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;

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

            $user->username = $data->username;
            $user->email = $data->email;
            $user->password = Hash::make($data->password);
            $user->name = $data->name;
            $user->surname = $data->surname;
            $user->profile_pic = isset($data->profile_pic) ? $data->profile_pic : $user->profile_pic;
            
            try{
                $user->save();
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
                    // Se envia la nueva contraseña al usuario
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

        if($user){
            $trades = Trade::where('user_id', $id)->get();

            $response = [
                "username" => $user->username,
                "profile_pic" => $user->profile_pic
                
                
                
            ];
        } else {
            $response = "Ese usuario no existe";
        }
        // Enviar la respuesta
        return $response;
    }
}
 