<?php

namespace App\Http\Helpers;
use \Firebase\JWT\JWT;

class MyJWT{

    private const KEY = '6GWEUdfbguIG57i7usosiosdhg546oidf';
    
    public static function generatePayload($user){
        $payload = array(            
            'username' => $user->username
        );

        return $payload;
    }

    public static function getKey(){
        return self::KEY;
    }

}