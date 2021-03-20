<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\user;
use App\Models\score;

use \Firebase\JWT\JWT;

class ScoreController extends Controller
{
    /**GET
     * Devuelve la lista de puntuaciones
     *
     * @return $response List of the scores
     */
    public function score_list()
    {   
        $response = [];  
        $scores = score::orderBy('experience','DESC')->get();

        foreach ($scores as $score) {
            $response[] = [
                'Username' => $score->user->username,
                'Experience' => $score->experience,
                'Profile pic' => $score->user->profile_pic
            ];
        }
        return response()->json($response);
    }    
}
