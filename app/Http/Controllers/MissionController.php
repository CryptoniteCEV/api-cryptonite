<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\mission;
use App\Validators\ValidateCoin;
use App\Constants\Gamification;


class MissionController extends ApiController
{
    
    /**
     * Genera las missiones en la base de datos
     */
    public function generate_missions(){
        $gamification = new Gamification();
        $missions = $gamification->get_all();
        foreach ($missions as $mission) {
            $response[] = mission::create([
                'icon' => $mission['icon'],
                'description' => $mission['description']
            ]);
        }
        return $this->successResponse($response,'Missions created', 200);
    }


}
