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
use \App\Helpers\InitiateEntry;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;

use App\Helpers\CoinGecko;

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

        $username = $request->get('username');
        $name = $request->get('name');
        $password = $request->get('password');
        $email = $request->get('email');
        $surname = $request->get('surname');
        $date_of_birth = $request->get('date_of_birth');
        $profile_pic = $request->get('profile_pic');

        $user = InitiateEntry::user($name,$password,$email,$username,$surname,$profile_pic,$date_of_birth);

        InitiateEntry::score($user->id);
        InitiateEntry::wallet($user->id, 1, 1000);
        return $this->successResponse($request,'User Created', 201);
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
                'username' => $user->username,
                'id' => $user->id
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

        $validator = $this->validatePassword();

        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $headers = getallheaders();
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
        
        $validator = ValidateUser::validate_uupdate();

        if ($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $headers = getallheaders();
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

        $quantity = $request->get('quantity');
        $is_sell = $request->get('is_sell');
        $coin = $request->get('coin');

        $coins_held = [];

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        $user = User::findOrFail($decoded->id);
        $currency = Currency::where('symbol', $coin)->firstOrFail();

        $wallets = Wallet::where('user_id', $user->id)->get();
        
        foreach ($wallets as $wallet) {
            $coins_held[] = [
                'currency_name' => $wallet->currency->name,
                'id' => $wallet->id
            ];
        }

        $validator_trade = ValidateUser::validate_trade();

        if ($validator_trade->fails()){
            return $this->errorResponse($validator_trade->messages(), 422);
        }

        $coin_position = array_search($currency->name, array_column($coins_held, 'currency_name'));
        $price = CoinGecko::getPrice($currency->name, 'usd');
        $converted_quantity = CoinGecko::convert_quantity($currency->name, $quantity, $is_sell);

        if($is_sell==1){
            if(!$coin_position){
                return $this->errorResponse('No funds on this coin',422);
            }else{
                $wallet_crypto = Wallet::find($coins_held[$coin_position]['id']);
                if($wallet_crypto->quantity<$quantity){
                    return $this->errorResponse('No funds on this coin',422);
                }
                $quantity = -$quantity;
                $wallet_dollar = Wallet::where('currency_id','1')->firstOrFail();
                $this->modify_wallet_quantities($wallet_crypto, $wallet_dollar, $quantity, $converted_quantity, $price);
            }
        }else{
            $quantity = - $quantity;
            $wallet_dollar = Wallet::where('currency_id','1')->firstOrFail();
            if($wallet_dollar->quantity<abs($quantity)){
                return $this->errorResponse('No funds on this coin',422);
            }

            if(!$coin_position){
                InitiateEntry::wallet($user->id,$currency->id,$converted_quantity);
                $wallet_dollar->quantity += $quantity;
                $wallet_dollar->save();
                
            }else{  
                $wallet_crypto = Wallet::find($coins_held[$coin_position]['id']);
                $this->modify_wallet_quantities($wallet_crypto, $wallet_dollar, $converted_quantity,$quantity,$price);
            }
        }
        $trade = InitiateEntry::trade($user->id, $currency->id, $is_sell, abs($price), abs($quantity));

        return $this->successResponse($trade, "Trade successfully created",200);

    }

    public function modify_wallet_quantities($wallet_crypto, $wallet_dollar, $quantity_crypto, $quantity_dollar, $price){

        $wallet_crypto->quantity += $quantity_crypto;
        $wallet_dollar->quantity += $quantity_dollar;
        $wallet_crypto->save();
        $wallet_dollar->save();
    }

    /**
     * GET info de usuario y sus tradeos
     */
    public function trades_info(Request $request){

        $user_info = [];
        $user = User::where('username', $request->get('username'))->firstOrFail();

        $user_info = [
            "username" => $user->username,
            "name" => $user->name,
            "profile_pic" => $user->profile_pic,
        ];
        
        for ($i=0; $i < count($user->currency); $i++) { 
            $user_info[$i]["price"] = $user->currency[$i]->pivot->price;
            $user_info[$i]["quantity"] = $user->currency[$i]->pivot->quantity;
            $user_info[$i]["currency"] = $user->currency[$i]->name;
        }
        
        return $this->successResponse($user_info, 201);
    }


}
 