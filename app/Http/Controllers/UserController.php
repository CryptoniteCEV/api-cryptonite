<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Score;
use App\Models\Currency;
use App\Models\Trade;
use App\Models\Wallet;
use App\Models\Following;

use \Firebase\JWT\JWT;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;

class UserController extends ApiController
{

    public function index()
    {
        $user = User::all();
        return $this->successResponse($user);
    }

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

        $validator = $this->validateUser();

        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $user = User::create([
            'name' => $request->get('name'),
            'password' => Hash::make($request->get('password')),
            'email' => $request->get('email'),
            'username' => $request->get('username'),
            'surname' => $request->get('surname'),
            'profile_pic' => $request->get('profile_pic'),
            'date_of_birth' => $request->get('date_of_birth')
        ]);

        //Crear score y asignar

        return $this->successResponse($user,'User Created', 201);
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

        $validator = $this->validateUsername();
        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $user = User::where('username',$request->username)->firstOrFail();
        if(Hash::check($request->password,$user->password)){

            $payload = array(            
                'username' => $user->username
            );

            $jwt = JWT::encode($payload, env('PRIVATE_KEY'));

            return $this->successResponse($jwt);
        }
        
        return $this->errorResponse('Password Wrong',401);
    }

     /**PUT
     * Restaurar contraseña del usuario que no puede hacer login. /users/restore/password
     *
     * Llega en la peticion el email del usuario que ha olvidado la contraseña. Se genera una nueva contraseña 
     * aleatoria y se le envia a su email
     *
     * @param $request Petición con el email del usuario
     * @return $response Respuesta de la api con la nueva contraseña del usuario
     */
    public function restore_password(Request $request){

        $validator = $this->validateEmail();
        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        $user = User::where('email',$request->email)->firstOrFail();

        $new_password = Str::random(15);
        $user->password = Hash::make($new_password);
        $user->save();
        return $this->successResponse($user, "Password´s been modified");
    }

    /**PUT
     * Cambiar la contraseña del usuario desde los ajustes de perfil de la app. /users/update/password
     * 
     * Llega en la petición la antigua contraseña del usuario y la nueva, si la contraseña antigua es correcta,
     * se cambia por la nueva contraseña.
     *
     * @param $request Petición con la antigua contraseña del usuario y la nueva
     * @return $response Respuesta de la api OK/Contraseña incorrecta
     */
    public function change_password(Request $request){

        $headers = getallheaders();
        if(!array_key_exists('Authorization', $headers)){
            
            return $this->errorResponse("Forbidden", 403);
        }
        
        $validator = $this->validatePassword();
        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        
        $separating_bearer = explode(" ", $headers['Authorization']);
        $jwt = $separating_bearer[1];
        
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));    
            
        $user = User::where('username', $decoded->username)->firstOrFail();
        $user->password = Hash::make($request->password);
        $user->save();

        return $this->successResponse($user, "Password´s been modified");
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
        
        $headers = getallheaders();
        
        if(!array_key_exists('Authorization', $headers)){
            
            return $this->errorResponse("Forbidden", 403);
        }

        $separating_bearer = explode(" ", $headers['Authorization']);
        $jwt = $separating_bearer[1];
        
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256")); 

        $user = User::where('username', $decoded->username)->firstOrFail();

        $response = [
            "username" => $user->username,
            "name" => $user->name,
            "surname" => $user->surname,
            "email" => $user->email,
            "profile_pic" => $user->profile_pic
        ];

        return $this->successResponse($response);
    }


    /**PUT
     * Cambiar los datos del usuario. /users/update/profile
     *
     * Llega en la petición la información que el usuario quiere modificar y se
     * actualiza en la base de datos.
     *
     * @param $request Petición con los datos a modificar del usuario
     * @return $response Confirmación de los cambios
     */
    public function update_profile(Request $request){

        $headers = getallheaders();
        
        if(!array_key_exists('Authorization', $headers)){
            
            return $this->errorResponse("Forbidden", 403);
        }
        
        $validator = $this->validateUpdate();

        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        
        $separating_bearer = explode(" ", $headers['Authorization']);
        $jwt = $separating_bearer[1];
        
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        $user = User::where('username', $decoded->username)->firstOrFail();

        $user->name = $request->has('name') ? $request->get('name') : $user->name;
        $user->surname = $request->has('surname') ? $request->get('surname') : $user->surname;
        $user->profile_pic = $request->has('profile_pic') ? $request->get('profile_pic') : $user->profile_pic;
        $user->date_of_birth = $request->has('date_of_birth') ? $request->get('date_of_birth') : $user->date_of_birth;
        
        $user->save();
        
        return $this->successResponse($user);
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
    public function follow_user(Request $request){
        
        $validator = $this->validateFollowing();

        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $headers = getallheaders();
        $separating_bearer = explode(" ", $headers['Authorization']);
        $jwt = $separating_bearer[1];
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        $follower = User::where('username', $decoded->username)->firstOrFail();
        
        $following = User::where('username', $request->get('username'))->firstOrFail();

        Following::create([
            'following_id' => $following->id,
            'follower_id' => $follower->id
        ]);

        return $this->successResponse("You are now following " . $following->username);
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
    public function get_followings(Request $request){

        $response = [];
        $headers = getallheaders();
        $separating_bearer = explode(" ", $headers['Authorization']);
        $jwt = $separating_bearer[1];
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        $user = User::where('username', $decoded->username)->get()->first();
        $followings_list = Following::where('follower_id', $user->id)->get();
        
        for ($i=0; $i < count($followings_list); $i++) { 
            $user_followed = User::find($followings_list[$i]->following_id);
            $response[] = [
                "username" => $user_followed->username,
                "profile_pic" => $user_followed->profile_pic
            ];
        }

        return $this->successResponse($response, 201);
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

        $response = [];
        $headers = getallheaders();
        $separating_bearer = explode(" ", $headers['Authorization']);
        $jwt = $separating_bearer[1];
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        $user = User::where('username', $decoded->username)->get()->first();
        $followers_list = Following::where('following_id', $user->id)->get();
        
        for ($i=0; $i < count($followers_list); $i++) { 
            $user_followed = User::find($followers_list[$i]->follower_id);
            $response[] = [
                "username" => $user_followed->username,
                "profile_pic" => $user_followed->profile_pic
            ];
        }

        return $this->successResponse($response, 201);
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
        //Decodificar el token
        $headers = getallheaders();
        $decoded = JWT::decode($headers['api_token'], env('PRIVATE_KEY'), array('HS256'));

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

    /**
     * TODO
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


    /**
     * Devuelve todos los usuarios
     */
    public function delete_user() {
        $response = [];

        //Decodificar el token
        $headers = getallheaders();
        //print_r(getallheaders());
        $decoded = JWT::decode($headers['api_token'], env('PRIVATE_KEY'), array('HS256'));
        $response = $headers['api_token'];
        // Buscar el usuario 
        $user = User::where('username', $decoded->username)->get()->first();
    
        if($user){

            try{
                $user->delete();
                $response = "Deleted";
            }catch(\Exception $e){
                $response = $e->getMessage();
            }
                        
        }else{
            $response = "No user with the same username";
        }

        return response()->json($response);
    
    }

    public function validateUser(){
        return Validator::make(request()->all(), [
            'name' => 'required|string|max:30',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'username' => 'required|string|max:25|unique:users',
            'surname' => 'required|string|max:50',
            'profile_pic' => 'url|null',
            'date_of_birth' => 'required|date'
        ]);
    }

    public function validateUsername(){
        return Validator::make(request()->all(), [
            'username' => 'required|string|max:25'
        ]);
    }

    public function validateEmail(){
        return Validator::make(request()->all(), [
            'email' => 'required|string|email|max:255'
        ]);
    }
    public function validatePassword(){
        return Validator::make(request()->all(), [
            'password' => 'required|string|min:6'
        ]);
    }

    public function validateUpdate(){
        return Validator::make(request()->all(), [
            'name' => 'string|max:30',
            'surname' => 'string|max:50',
            'profile_pic' => 'url',
            'date_of_birth' => 'date'
        ]);
    }

    public function validateFollowing(){
        return Validator::make(request()->all(), [
            'username' => 'string|required|max:25'
        ]);
    }
    
}
 