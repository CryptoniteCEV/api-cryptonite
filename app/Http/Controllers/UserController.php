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

use App\Validators\ValidateUser;
use App\Validators\ValidateCoin;

use \Firebase\JWT\JWT;
use \App\Helpers\Token;

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

        $validator = ValidateUser::validate_create();

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

        $this->initiate_score($user->id);
        $this->initiate_wallet($user->id, 1, 1000);
        return $this->successResponse($user,'User Created', 201);
    }

    public function initiate_score($id){

        $score = Score::create([
            'user_id' => $id
        ]);
        
    }
    public function initiate_wallet($user_id, $currency_id, $quantity){
        Wallet::create([
            'quantity' => $quantity,
            'user_id' => $user_id,
            'currency_id' => $currency_id
        ]);
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

        $validator = ValidateUser::validate_username();
        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        //decode
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

        $validator = ValidateUser::validate_email();
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
        if(!Token::is_auth_passed()){
            
            return $this->errorResponse("Forbidden", 403);
        }
        
        $validator = $this->validatePassword();
        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        
        $jwt = Token::get_token_from_headers($headers);
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
        
        if(!Token::is_auth_passed()){
            
            return $this->errorResponse("Forbidden", 403);
        }

        $jwt = Token::get_token_from_headers($headers);
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
        
        if(!Token::is_auth_passed($headers)){
            
            return $this->errorResponse("Forbidden", 403);
        }
        
        $validator = ValidateUser::validate_uupdate();

        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        
        $jwt = Token::get_token_from_headers($headers); 
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
        
        $validator = ValidateUser::validate_following();

        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
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
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        $user = User::where('username', $decoded->username)->firstOrFail();
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
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        $user = User::where('username', $decoded->username)->firstOrFail();
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

    /**PUT
     * Actualizar la experiencia del usuario. /users/update/score
     *
     * Obtiene el usuario por su token y actualiza su experiencia (xp) que llega por url
     * en la tabla de scores.
     *
     * @param $request
     * @param $newExp La cantidad de experiencia que tiene el usuario para actualizar en la tabla scores
     * @return $response Mensaje de confirmación
     */
    public function update_user_exp(Request $request){

        $validator = ValidateUser::validate_exp();
        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'), array("HS256"));

        $user = User::where('username', $decoded->username)->firstOrFail();
        $score = Score::where('user_id', $user->id)->firstOrFail();

        $score->experience = $request->get('new_exp');
        $score->save();
        return $this->successResponse($score,"Exp. updated", 201);
    }

    /**POST
     * Vender cantidad de cryptos que posea el usuario en su wallet
     * Falta hacer que se pueda comprar
     * 
     */
    public function trade_coin(Request $request){

        $coins_held = [];
        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        $user = User::where('username', $decoded->username)->firstOrFail();
        $currency = Currency::where('name', $request->get('coin'))->firstOrFail();

        $wallets = Wallet::where('user_id', $user->id)->get();

        foreach ($wallets as $wallet) {
            $coins_held[] = [
                'currency_name' => $wallet->currency->name,
                'id' => $wallet->id
            ];
        }

        if($request->get('is_sell')=='true'){
            $quantity = -$request->get('quantity');
            $price = $request->get('quantity');
            $this->generate_trade($user, $currency, $request, $coins_held, $quantity , $price);

        }else{
            
            $quantity = $request->get('quantity');
            $price = -$request->get('quantity');
            $this->generate_trade($user, $currency, $request, $coins_held, $quantity , $price);

        }

        return $this->successResponse($user, "Ha funcionado",200);

    }

    public function generate_trade($user, $currency, $request, $coins_held, $quantity, $price){

        $coin_position = array_search($currency->name, array_column($coins_held, 'currency_name'));

        if(!$coin_position){

            $this->initiate_wallet($user->id,$currency->id,$request->get('quantity'));
            $wallet_dollar = Wallet::find(1);
            //Esto realmente no deberia ser quantity sino el precio en dollars (need coingecko request)
            $wallet_dollar->quantity += $price;
            $wallet_dollar->save();
            
        }else{  
            $wallet_crypto = Wallet::find($coins_held[$coin_position]['id']);
            $wallet_dollar = Wallet::find(1);
            $wallet_crypto->quantity += $quantity;
            //Esto realmente no deberia ser quantity sino el precio en dollars (need coingecko request)
            $wallet_dollar->quantity += $price;
            $wallet_crypto->save();
            $wallet_dollar->save();
        }
        $this->initiate_trade($user->id, $currency->id,$request, $request->get('quantity'));
    }

    public function initiate_trade($user_id, $currency_id, $request, $price){
        //price es el precio en dollars no quantity
        Trade::create([
            'price' => $price,
            'quantity' => $request->get('quantity'),
            'is_sell' => false,//$request->get('is_sell'),
            'user_id' => $user_id,
            'currency_id' => $currency_id
        ]);
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
    /*public function delete_user() {
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
    
    }*/

}
 