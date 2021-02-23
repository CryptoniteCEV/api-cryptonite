<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use \Firebase\JWT\JWT;

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
        $headers = getallheaders();
        if(array_key_exists('Authorization', $headers)){
        
            $separating_bearer = explode(" ", $headers['Authorization']);
            $jwt = $separating_bearer[1];
            
            $decoded = JWT::decode($jwt, env('PRIVATE_KEY'));        
        
            if($decoded){

                return $next($request);

            }else{
                abort(403, "Forbidden");
            }
        }else{
            abort(403, "Token not passed");
        }
    }
}
