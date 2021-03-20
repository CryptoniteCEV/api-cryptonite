<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Hash;

use App\Models\user;
use App\Models\score;
use App\Models\trade;
use App\Models\wallet;
use App\Models\userMission;
use App\Constants\Coin;

class InitiateEntry{

    public static function user($name, $password, $email, $username, $profile_pic){
        return user::create([
            'name' => $name,
            'password' => Hash::make($password),
            'email' => $email,
            'username' => $username,
            'profile_pic' => $profile_pic
        ]);
    }

    public static function trade($user_id, $currency_id, $is_sell, $price, $quantity, $date){
        return trade::create([
            'price' => $price,
            'quantity' => $quantity,
            'is_sell' => $is_sell,
            'user_id' => $user_id,
            'currency_id' => $currency_id,
            'date' => $date
        ]);
    }

    public static function wallet($user_id){
        $coin = new Coin();
        $coins = $coin->get_all();
        for ($i=1; $i < count($coins)+1; $i++) { 
            if($i == 1){
                $quantity = 1000;
            }else{
                $quantity = 0;
            }
            wallet::create([
                'quantity' => $quantity,
                'user_id' => $user_id,
                'currency_id' => $i
            ]);
        }
        
    }

    public static function score($id){

        $score = score::create([
            'user_id' => $id
        ]);
        
    }

    public static function missions($id){
        $missions_ids = [13, 11, 3];

        for ($i=0; $i < count($missions_ids) ; $i++) { 
            
            $mission = UserMission::create([
                'user_id' => $id,
                'mission_id' => $missions_ids[$i],
                'is_finished' => 0
            ]);
        }
    }
}
