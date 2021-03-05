<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\Score;
use App\Models\Trade;
use App\Models\Wallet;
use App\Constants\Coin;

class InitiateEntry{

    public static function user($name, $password, $email, $username, $surname, $profile_pic, $date_of_birth){
        //price es el precio en dollars no quantity
        return User::create([
            'name' => $name,
            'password' => Hash::make($password),
            'email' => $email,
            'username' => $username,
            'surname' => $surname,
            'profile_pic' => $profile_pic,
            'date_of_birth' => $date_of_birth
        ]);
    }

    public static function trade($user_id, $currency_id, $is_sell, $price, $quantity, $date){
        //price es el precio en dollars no quantity
        return Trade::create([
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
        for ($i=1; $i < count($coins); $i++) { 
            if($i == 1){
                $quantity = 1000;
            }else{
                $quantity = 0;
            }
            Wallet::create([
                'quantity' => $quantity,
                'user_id' => $user_id,
                'currency_id' => $i
            ]);
        }
        
    }

    public static function score($id){

        $score = Score::create([
            'user_id' => $id
        ]);
        
    }
}