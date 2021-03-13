<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class userMission extends Model
{
    use HasFactory;

    protected $fillable = ['is_finished', 'user_id', 'mission_id'];

    public function mission(){
    	return $this->belongsTo(mission::class);
    }
}
