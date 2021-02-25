<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\Token;
use \Firebase\JWT\JWT;
use App\Traits\ApiResponser;
use Firebase\Auth\Token\Exception\InvalidToken;

class EnsureTokenIsValid
{
    use ApiResponser;
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

        if(!Token::is_auth_passed($headers)){
            return $this->errorResponse("Forbidden", 403);
        }
        
        $jwt = Token::get_token_from_headers($headers);
        $decoded = JWT::decode($jwt, env('PRIVATE_KEY'), array('HS256'));        
        
        return $next($request);
    }
}
