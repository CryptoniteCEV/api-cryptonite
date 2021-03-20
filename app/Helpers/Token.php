<?php
namespace App\Helpers;
class Token{

    //Separa el token recibido en headers y devuelve el token especifico
    public static function get_token_from_headers($headers){
        $auth_exploded = explode(" ", $headers['Authorization']);
        return $auth_exploded[1];
    }

    //comprueba si se ha enviado el token
    public static function is_auth_passed($headers){
        return array_key_exists('Authorization', $headers);
    }
}