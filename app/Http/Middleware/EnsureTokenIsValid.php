<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use \Firebase\JWT\JWT;

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
        define("ADMIN","Administrator");
        
        $key = MyJWT::getKey();

        $headers = getallheaders();

        $decoded = JWT::decode($headers['api_token'], $key, array('HS256'));
        
        if($decoded){

            if($decoded->role === ADMIN){
                return $next($request);
            }else{
                abort(403, "¡Usted no está permitido aquí!");
            }

        }else{
            abort(403, "¡Token vacío!");
        }
    }
}
