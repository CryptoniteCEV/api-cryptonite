<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use \Firebase\JWT\JWT;
use App\Http\Helpers\MyJWT;

use Firebase\Auth\Token\Exception\InvalidToken;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {        
        $key = MyJWT::getKey();

        $headers = getallheaders();

        $api_token = $headers['api_token'];

        $alg = 'HS256';

        $sign = JWT::sign($api_token, $key, $alg);

        $decoded = JWT::decode($api_token, $key, array($alg));        
        
        if(isset($decoded->username) && ($decoded->username === "Sharkteeth89")){

            return $next($request);

        }else{
            abort(403, "No autorizado");
        }
    }
}
