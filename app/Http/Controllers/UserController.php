<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\user;
use App\Models\score;
use App\Models\currency;
use App\Models\trade;
use App\Models\wallet;
use App\Models\following;
use App\Models\userMission;
use App\Models\mission;

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
        $user = user::all();
        return $this->successResponse($user);
    }

    /**POST
     * Registro de usuarios en la app. /users/register 
     *
     * Llega en la petición el username, email y contraseña, nombre y profile pic
     * Añade el nuevo usuario en la base de daos si la información es correcta.
     * 
     * @param $request Petición con los datos del usuario
     * @return $response respuesta de la api OK
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
        $profile_pic = $request->get('profile_pic');

        $user = InitiateEntry::user($name,$password,$email,$username,$profile_pic);

        InitiateEntry::score($user->id);
        InitiateEntry::wallet($user->id);
        InitiateEntry::missions($user->id);
        return $this->successResponse($user,'User Created', 200);
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
        
        try{
            $user = user::where('username',$request->username)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }
        
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

        try{
            $user = user::where('email',$request->email)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

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
            
        try{
            $user = user::where('username', $decoded->username)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

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

        try{
            $user = user::where('username', $decoded->username)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }
        
        $response = [
            "username" => $user->username,
            "name" => $user->name,
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

        try{
            $user = user::where('username', $decoded->username)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        $user->name = $request->has('name') ? $request->get('name') : $user->name;
        $user->profile_pic = $request->has('profile_pic') ? $request->get('profile_pic') : $user->profile_pic;
        
        $user->save();
        
        return $this->successResponse($user);
    }

    /**POST
     * Seguir a un usuario. /users/follow/{username}
     * 
     * Llega por param el nombre de usuario al que se va a seguir y se decodifica el token para obtener
     * el id del usuario que va a seguir. Se crea la fila en la tabla de followings.
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

        try{
            $follower = user::where('username', $decoded->username)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }
        
        $following = user::where('username', $request->get('username'))->firstOrFail();

        following::create([
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

        try{
            $user = user::where('username', $decoded->username)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        $followings_list = following::where('follower_id', $user->id)->get();
        
        for ($i=0; $i < count($followings_list); $i++) { 
            $user_followed = user::find($followings_list[$i]->following_id);
            $response[] = [
                "username" => $user_followed->username,
                "profile_pic" => $user_followed->profile_pic
            ];
        }

        return $this->successResponse($response, 200);
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

        try{
            $user = user::where('username', $decoded->username)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        $followers_list = following::where('following_id', $user->id)->get();
        
        for ($i=0; $i < count($followers_list); $i++) { 
            $user_followed = user::find($followers_list[$i]->follower_id);
            $response[] = [
                "username" => $user_followed->username,
                "profile_pic" => $user_followed->profile_pic
            ];
        }

        return $this->successResponse($response, 200);
    }

    /**PUT
     * Actualizar la experiencia del usuario. /users/update/score
     *
     * Obtiene el usuario por su token y actualiza su experiencia (xp) que llega por params
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

        try{
            $user = user::where('username', $decoded->username)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        try{
            $score = score::where('user_id', $user->id)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("Score not found",401);
        }

        $score->experience = $request->get('new_exp');
        $score->save();
        return $this->successResponse($score,"Exp. updated", 200);
    }

    /**
     * Recoge y devuelve la experiencia actual del usuario recibido por token
     */
    public function getUserExperience(){

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'), array("HS256"));

        try{
            $user = user::where('username', $decoded->username)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        return $user->score->experience;

    }

    /**POST
     * Compra venta de monedas con un fee que se encargará de evitar problemas de fluctuaciones
     * 
     */
    public function trade_coin(Request $request){

        $quantity = $request->get('quantity');
        $fee = 0.1 * $quantity/100;
        $quantity = $quantity - $fee;

        $is_sell = $request->get('is_sell');
        $coin = $request->get('coin');

        $coins_held = [];

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        try{
            $user = user::findOrFail($decoded->id);
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        try{
            $currency = currency::where('symbol', $coin)->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("Coin not found",401);
        }
    
        $wallets = wallet::where('user_id', $user->id)->get();
        
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
        $converted_quantity = CoinGecko::convert_quantity($currency->name, $quantity, $is_sell);
        
        $price = CoinGecko::getPrice($currency->name, 'usd');
            
        $date = getDate();
        
        if($is_sell==1){
            
            $wallet_crypto = wallet::find($coins_held[$coin_position]['id']);
            
            if($wallet_crypto->quantity==0){
                return $this->errorResponse('No funds on this coin',422);
            }else{
                if($wallet_crypto->quantity<$quantity){
                    return $this->errorResponse('No funds on this coin',422);
                }
                $quantity = -$quantity;
                
                try{
                    $wallet_dollar = $wallets[0];
                }catch(\Exception $e){
                    return $this->errorResponse("Wallet not found",401);
                }
                $this->modify_wallet_quantities($wallet_crypto, $wallet_dollar, $quantity, $converted_quantity, $currency->name);
            }
        }else{
            $quantity = - $quantity;
            try{
                $wallet_dollar = $wallets[0];
            }catch(\Exception $e){
                return $this->errorResponse("Wallet not found",401);
            }
            if($wallet_dollar->quantity<abs($quantity)){
                return $this->errorResponse('No funds on this coin',422);
            }
                $wallet_crypto = wallet::find($coins_held[$coin_position]['id']);
                $this->modify_wallet_quantities($wallet_crypto, $wallet_dollar, $converted_quantity,$quantity, $currency->name);
        }

        $trade = new trade();
        $trade->price = $price;
        $trade->quantity = abs($quantity);
        $trade->is_sell = $is_sell;
        $trade->user_id = $user->id;
        $trade->currency_id = $currency->id;
        $trade->date = $date[0];

        InitiateEntry::trade($user->id, $currency->id, $is_sell, $price, abs($quantity), $date[0]);
            
        return $this->successResponse($trade, "Trade successfully created",200);

    }

    //Actualiza los wallets modificados
    public function modify_wallet_quantities($wallet_crypto, $wallet_dollar, $quantity_crypto, $quantity_dollar, $currency){

        $wallet_crypto->quantity += $quantity_crypto;
        $wallet_dollar->quantity += $quantity_dollar;

        $wallet_crypto->save();

        if($wallet_dollar->quantity < 2){
            $wallet_dollar->quantity = 0;
        }

        if(CoinGecko::convert_quantity($currency, $wallet_crypto->quantity, 1) < 2){
            $wallet_crypto->quantity = 0;
            $wallet_crypto->save();
        }

        $wallet_dollar->save();
    }

    /**
     * GET info de usuario pasado por params y sus tradeos
     */
    public function trades_profile_info(Request $request){

        $user_info = [];
        $user_info["Trades"] = [];
        
        try{
            $user = user::where('username', $request->get('username'))->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }
        $user_info["User"] = [
            "Username" => $user->username,
            "Name" => $user->name,
            "Profile_pic" => $user->profile_pic
        ];
        
        for ($i=0; $i < count($user->currency); $i++) { 
            $user_info["Trades"][$i]["Quantity"] = $user->currency[$i]->pivot->quantity;
            
            if($user->currency[$i]->pivot->is_sell == 1){
                $user_info["Trades"][$i]["Converted"] = $user->currency[$i]->pivot->quantity * $user->currency[$i]->pivot->price;
            }else{
                $user_info["Trades"][$i]["Converted"] = $user->currency[$i]->pivot->quantity / $user->currency[$i]->pivot->price;
            }
            
            if($user->currency[$i]->pivot->is_sell == 1){
                $user_info["Trades"][$i]["Coin_from"] = $user->currency[$i]->name;
                $user_info["Trades"][$i]["Coin_from_symbol"] = $user->currency[$i]->symbol;
                $user_info["Trades"][$i]["Coin_to_symbol"] = "USDT";
                $user_info["Trades"][$i]["Coin_to"] = "Tether";
            }else{
                $user_info["Trades"][$i]["Coin_to"] = $user->currency[$i]->name;
                $user_info["Trades"][$i]["Coin_from"] = "Tether";
                $user_info["Trades"][$i]["Coin_to_symbol"] = $user->currency[$i]->symbol;
                $user_info["Trades"][$i]["Coin_from_symbol"] = "USDT";
            }
        }
        
        return $this->successResponse($user_info, 200);
    }

    /**
     * GET info de usuario recibido por token y sus tradeos
     */
    public function trades_info(Request $request){

        $user_info = [];
        
        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        try{
            $user = user::findOrFail($decoded->id);
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }
        
        for ($i=0; $i < count($user->currency); $i++) { 
            $user_info[$i]["Price"] = $user->currency[$i]->pivot->price;
            $user_info[$i]["Is_sell"] = $user->currency[$i]->pivot->is_sell;
            $user_info[$i]["Quantity"] = $user->currency[$i]->pivot->quantity;
            $user_info[$i]["Coin"] = $user->currency[$i]->name;
            $user_info[$i]["Date"] = $user->currency[$i]->pivot->date;
        }
        
        return $this->successResponse($user_info, 200);
    }

    /**
     * Recoge y devuelve todos los usuarios
     */
    public function get_users(){
        
        $response = [];
        $users = user::all();

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));
        $index = 0;
        for ($i=0; $i < count($users); $i++) { 
            
            if($decoded->username != $users[$i]->username){
                
                $response[$index] = [
                    "Username" => $users[$i]->username,
                    "Exp" => $users[$i]->score->experience,
                    "ProfilePic" => $users[$i]->profile_pic
                ];
                $index += 1;
            }
        }
        return $this->successResponse($response, 200);
        
    }

    /**
     * Devuelve la informacion del usuario pasado por param
     */
    public function user_info(Request $request){

        try{
            $user = user::findOrFail($request->get('id'));
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }
        
        $response = [
            "username" => $user->username,
            "name" => $user->name,
            "profile_pic" => $user->profile_pic
        ];

        return $this->successResponse($response);
    }

    /**
     * Unfollow de la persona pasada por param
     */
    public function stopFollowing(Request $request){

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        try{
            $follower = user::findOrFail($decoded->id);
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }
        try{
            $following = user::where('username', $request->get('username'))->firstOrFail();
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        $followings = following::where('follower_id', $follower->id)->where('following_id', $following->id)->firstOrFail();

        $followings->delete();

        return $this->successResponse($followings, "You are not following " . $following->username . " anymore"  ,200);

    }

    /**
     * Asigna una nueva mision aleatoria al usuario pasado por token ademas de eliminar la ya comlpletada recibida por token
     */
    public function assignNewRandMission(Request $request){

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        try{
            $user = user::findOrFail($decoded->id);
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }
        //delete
        userMission::where('user_id', $decoded->id)->where('mission_id', $request->get('id'))->firstOrFail()->delete();
        
        //getAllMissions
        $missions = mission::all();

        //getUserMissions
        $userMissions = $user->mission;
        $user_missions_id = [];
        
        foreach ($userMissions as $userMission) {
            $user_missions_id[] = $userMission->id;
        }

        do {
            //generateRandom
            $new_mission_id = rand(1, count($missions));
            
        }while(in_array($new_mission_id , $user_missions_id) || $new_mission_id==$request->get('id') || $new_mission_id == 12 || $new_mission_id == 13);
        

        $new_user_mission = userMission::create([
            'user_id' => $decoded->id,
            'mission_id' => $new_mission_id,
            'is_finished' => 0
        ]);

        $all_missions = [];

        $all_missions[0]["id"] = $new_user_mission->mission->id;
        $all_missions[0]["is_finished"] = $new_user_mission->is_finished;
        $all_missions[0]["icon"] = $new_user_mission->mission->icon;
        $all_missions[0]["description"] = $new_user_mission->mission->description;

        
        for ($i=0; $i <count($userMissions) ; $i++) { 
            
            $all_missions[$i + 1]["id"] = $userMissions[$i]->id;
            $all_missions[$i + 1]["is_finished"] = $userMissions[$i]->pivot->is_finished;
            $all_missions[$i + 1]["icon"] = $userMissions[$i]->icon;
            $all_missions[$i + 1]["description"] = $userMissions[$i]->description;
        }

        return $this->successResponse($all_missions, "Mission deleted, new mission assigned" ,200);
    }

    /**
     * Actualiza el estado de la mission que se acaba de completar en caso de sea ua de las 3 poseidas por el usuario
     */
    public function updateMission(Request $request){

        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        try{
            $user = user::findOrFail($decoded->id);
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        $userMissions = $user->mission;
        $user_missions_id = [];
        
        foreach ($userMissions as $userMission) {
            $user_missions_id[] = $userMission->id;
        }
        
        if($id = in_array($request->get('id') , $user_missions_id)){
            $user_mission = userMission::where('mission_id', $request->get('id'))->where('user_id', $user->id)->firstOrFail();
            if($user_mission->is_finished == 0){
                $user_mission->is_finished = 1;
                $user_mission->save();
                return $this->successResponse($user_mission, "Mission Ready To Be Claimed" ,200);
            }else{
                return $this->successResponse($user_mission, "Mission already shown" ,202);
            }
            
        }
            
        return $this->errorResponse("Mission not active" , 300);;
        
    }

    /**
     * Devuelve toda la información del usuario y sus misiones util para la pantalla de gamificacion
     */
    public function getUserGamification(){

        $gamification = [];
        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        try{
            $user = user::findOrFail($decoded->id);
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        $userMissions = $user->mission;

        $gamification["User"] = [
            "Username" => $user->username,
            "Exp" => $user->score->experience,
            "ProfilePic" => $user->profile_pic
        ];

        for ($i=0; $i <count($userMissions) ; $i++) { 
            
            $gamification["Missions"][$i] = [
                "id" => $userMissions[$i]->id,
                "is_finished" => $userMissions[$i]->pivot->is_finished,
                "icon" => $userMissions[$i]->icon,
                "description" => $userMissions[$i]->description
            ];
        }

        return $this->successResponse($gamification ,200);
    }

    /**
     * Devielve todas las misiones del usuario pasado por token
     */
    public function getMissions(){

        $gamification = [];
        $headers = getallheaders();
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'),array("HS256"));

        try{
            $user = user::findOrFail($decoded->id);
        }catch(\Exception $e){
            return $this->errorResponse("User not found",401);
        }

        $userMissions = $user->mission;

        for ($i=0; $i <count($userMissions) ; $i++) { 
            
            $gamification[$i] = [
                "id" => $userMissions[$i]->id,
                "is_finished" => $userMissions[$i]->pivot->is_finished,
                "icon" => $userMissions[$i]->icon,
                "description" => $userMissions[$i]->description
            ];
        }

        return $this->successResponse($gamification ,200);
    }

    
}
 
