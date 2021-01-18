<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

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
}
