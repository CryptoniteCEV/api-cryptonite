<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Score;

use App\Http\Helpers\MyJWT;
use \Firebase\JWT\JWT;

class ScoreController extends Controller
{
    /**GET
     * Retrives a list of all the scores ordered by experience bigger->lower
     *
     * @return $response List of the scores
     */
    public function score_list()
    {     
        $scores = Score::orderBy('experience','DESC')->get(); 
        if ($scores) {
            for ($i=0; $i <count($scores) ; $i++) { 
                $response[$i] = [
                    "Username" => $scores[$i]->user->username,
                    "Level" => $scores[$i]->level,
                    "Experience" => $scores[$i]->experience,
                    "Profile pic" => $scores[$i]->user->profile_pic
                ];
            }        
        }else{
            $response = "No scores found";
        }
        return response($response);
    }
    
}
